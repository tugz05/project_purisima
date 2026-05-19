<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffTransactionUpdateRequest;
use App\Models\CertificateTemplate;
use App\Models\Transaction;
use App\Services\AIDocumentGeneratorService;
use App\Services\CertificateTemplateService;
use App\Services\CertificateVerificationService;
use App\Services\ManualCertificateWizardService;
use App\Services\NotificationService;
use App\Services\TemplateTwoAIService;
use App\Services\TemplateThreeAIService;
use App\Services\TransactionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TransactionService $transactionService,
        private NotificationService $notificationService,
        private CertificateTemplateService $templateService,
        private AIDocumentGeneratorService $aiService,
        private TemplateTwoAIService $templateTwoAiService,
        private TemplateThreeAIService $templateThreeAiService,
        private ManualCertificateWizardService $manualCertificateWizardService,
        private CertificateVerificationService $certificateVerificationService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'staff_id', 'payment_status', 'search', 'sort', 'direction']);
        $perPage = $request->get('per_page', 15);

        $transactions = $this->transactionService->getStaffTransactions($filters, $perPage, $request->user());
        $statistics = $this->transactionService->getStatistics();
        $transactionTypes = $this->transactionService->getTransactionTypes();

        return Inertia::render('Staff/transactions/Index', [
            'transactions' => [
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
            'statistics' => $statistics,
            'transactionTypes' => $transactionTypes,
            'filters' => $filters,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        // Refresh to get latest data including officer_of_the_day
        $transaction->refresh();
        $transaction->load(['resident', 'staff', 'documentType']);

        // Get default template if available
        $defaultTemplate = null;
        if ($transaction->document_type_id) {
            $defaultTemplate = CertificateTemplate::getDefaultForDocumentType($transaction->document_type_id);
        }

        return Inertia::render('Staff/transactions/Show', [
            'transaction' => $transaction,
            'defaultTemplate' => $defaultTemplate,
        ]);
    }

    public function update(StaffTransactionUpdateRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        try {
            $oldStatus = $transaction->status;

            // Get validated data and ensure officer_of_the_day is included
            $validated = $request->validated();

            // Explicitly include officer_of_the_day if it exists in the request (even if null/empty)
            // Use array_key_exists to check for the key, not has() which returns false for empty strings
            if (array_key_exists('officer_of_the_day', $request->all())) {
                $officerValue = $request->input('officer_of_the_day');
                // Convert empty string to null, otherwise use the trimmed value
                $validated['officer_of_the_day'] = is_string($officerValue) && trim($officerValue) === '' ? null : $officerValue;
            }

            Log::info('Updating transaction', [
                'transaction_id' => $transaction->id,
                'officer_of_the_day_in_request' => array_key_exists('officer_of_the_day', $request->all()),
                'officer_of_the_day_value' => $request->input('officer_of_the_day'),
                'officer_of_the_day_in_validated' => $validated['officer_of_the_day'] ?? 'NOT IN VALIDATED',
                'validated_keys' => array_keys($validated),
            ]);

            $this->transactionService->updateStatus(
                $transaction,
                $request->status,
                $request->user(),
                $validated
            );

            // Refresh to verify it was saved
            $transaction->refresh();
            Log::info('After update - officer_of_the_day value', [
                'saved_value' => $transaction->officer_of_the_day,
                'transaction_id' => $transaction->id,
            ]);

            // Create notification for transaction status change
            if ($oldStatus !== $request->status) {
                // Load relationships before creating notification
                $transaction->load(['documentType', 'resident']);
                $this->createTransactionNotification($transaction, $request->status);
            }

            // Return with the updated transaction data
            return redirect()->route('staff.transactions.show', $transaction->fresh())
                ->with('success', 'Transaction updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update transaction: '.$e->getMessage()]);
        }
    }

    public function assign(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $this->transactionService->updateStatus(
            $transaction,
            'in_progress',
            $request->user()
        );

        return redirect()->back()
            ->with('success', 'Transaction assigned to you successfully.');
    }

    private function paymentRequired(Transaction $transaction): bool
    {
        return $transaction->requiresPayment() && ! $transaction->isPaymentCompleted();
    }

    /**
     * Load template content and process with resident data
     */
    public function loadTemplate(Transaction $transaction, Request $request): JsonResponse
    {
        $this->authorize('update', $transaction);

        if ($this->paymentRequired($transaction)) {
            return response()->json([
                'error' => 'Payment must be completed before generating the certificate.',
            ], 403);
        }

        $templateId = $request->get('template_id');

        if ($templateId) {
            $template = CertificateTemplate::find($templateId);
        } else {
            // Get default template
            $template = $transaction->document_type_id
                ? CertificateTemplate::getDefaultForDocumentType($transaction->document_type_id)
                : null;
        }

        if (! $template) {
            return response()->json([
                'error' => 'No template found for this document type',
            ], 404);
        }

        $transaction->loadMissing('resident');
        $residentData = $transaction->resolveCertificateResidentData();

        // Process template
        $processedContent = $this->templateService->processTemplate($template, $residentData);

        return response()->json([
            'content' => $processedContent,
            'template_name' => $template->name,
        ]);
    }

    /**
     * Generate certificate content using AI
     */
    public function generateWithAI(Transaction $transaction, Request $request): JsonResponse
    {
        $this->authorize('update', $transaction);

        if ($this->paymentRequired($transaction)) {
            return response()->json([
                'error' => 'Payment must be completed before generating the certificate.',
            ], 403);
        }

        try {
            // Refresh transaction to get latest data including officer_of_the_day
            $transaction->refresh();
            $transaction->load(['resident', 'documentType']);

            if (! $transaction->documentType) {
                return response()->json([
                    'error' => 'Document type not found for this transaction.',
                ], 404);
            }

            // Template Two — background-image layout with structured field data
            if ($transaction->documentType->template_type === 'template_two') {
                $templateTwoData = $this->templateTwoAiService->generateContent($transaction);

                $existing = is_array($transaction->generated_document_data) ? $transaction->generated_document_data : [];
                $transaction->update([
                    'generated_document_data' => array_merge($existing, [
                        'template_two_data' => $templateTwoData,
                        'generated_at' => now()->toIso8601String(),
                        'generated_by' => $request->user()?->id,
                    ]),
                    'document_generated_at' => now(),
                ]);

                return response()->json([
                    'content' => '',
                    'template_two_data' => $templateTwoData,
                    'is_template_two' => true,
                    'generated_by' => 'AI (OpenAI)',
                    'success' => true,
                ]);
            }

            // Template Three — positioned overlay on permit/vehicle clearance background
            if ($transaction->documentType->template_type === 'template_three') {
                $templateThreeData = $this->templateThreeAiService->generateContent($transaction);

                $existing = is_array($transaction->generated_document_data) ? $transaction->generated_document_data : [];
                $transaction->update([
                    'generated_document_data' => array_merge($existing, [
                        'template_three_data' => $templateThreeData,
                        'generated_at' => now()->toIso8601String(),
                        'generated_by' => $request->user()?->id,
                    ]),
                    'document_generated_at' => now(),
                ]);

                return response()->json([
                    'content' => '',
                    'template_three_data' => $templateThreeData,
                    'is_template_three' => true,
                    'generated_by' => 'AI (OpenAI)',
                    'success' => true,
                ]);
            }

            // Standard flow: generate HTML prose content
            $content = $this->aiService->generateCertificateContent($transaction);

            return response()->json([
                'content' => $content,
                'generated_by' => 'AI (OpenAI)',
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Generation Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'AI generation failed: '.$e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Print certificate as HTML
     */
    public function printCertificate(Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        if ($this->paymentRequired($transaction)) {
            abort(403, 'Payment must be completed before printing the certificate.');
        }

        // Refresh the transaction to get the latest data
        $transaction->refresh();
        $transaction->load(['resident', 'documentType', 'staff']);

        if (! $transaction->documentType) {
            abort(404, 'Document type not found for this transaction.');
        }

        $generatedData = is_array($transaction->generated_document_data) ? $transaction->generated_document_data : [];
        $templateType = $transaction->documentType->template_type;
        $content = $generatedData['content'] ?? '';
        $templateTwoData = $generatedData['template_two_data'] ?? null;
        $templateThreeData = $generatedData['template_three_data'] ?? null;

        // Background-image templates only need their structured data; others need HTML content
        if ($templateType === 'template_two') {
            if ($templateTwoData === null) {
                abort(404, 'Certificate content not found. Please generate the certificate first.');
            }
        } elseif ($templateType === 'template_three') {
            if ($templateThreeData === null) {
                abort(404, 'Certificate content not found. Please generate the certificate first.');
            }
        } elseif (empty($content)) {
            abort(404, 'Certificate content not found. Please generate the certificate first.');
        }

        $resident = $transaction->resident;
        $documentTypeName = $transaction->documentType->name;
        $currentDate = now()->format('F d, Y');
        $currentDateFormatted = now()->format('jS \d\a\y \o\f F, Y');
        $officerOfTheDay = $transaction->officer_of_the_day;

        $printLayout = $this->manualCertificateWizardService->resolvePrintLayout($transaction->documentType);

        $token = $this->certificateVerificationService->ensureVerificationToken($transaction);
        $verificationUrl = url(route('certificate.verify', ['token' => $token], false));

        return Inertia::render('Staff/transactions/PrintCertificate', [
            'transaction' => $transaction,
            'resident' => $resident,
            'documentTypeName' => $documentTypeName,
            'printLayout' => $printLayout,
            'templateType' => $templateType,
            'templateTwoData' => $templateTwoData,
            'templateThreeData' => $templateThreeData,
            'content' => $content,
            'currentDate' => $currentDate,
            'currentDateFormatted' => $currentDateFormatted,
            'officerOfTheDay' => $officerOfTheDay ?? null,
            'verificationUrl' => $verificationUrl,
            'previewQrUrl' => null,
        ]);
    }

    public function printReceipt(Transaction $transaction, Request $request): Response
    {
        $transaction->refresh();
        $transaction->load(['resident', 'documentType', 'paymentVerifier', 'staff']);

        if ($transaction->payment_status !== 'paid') {
            abort(404, 'Receipt is only available for paid transactions.');
        }

        return Inertia::render('Staff/transactions/PrintReceipt', [
            'transaction' => [
                'id'                => $transaction->id,
                'transaction_id'    => $transaction->transaction_id,
                'title'             => $transaction->title,
                'fee_amount'        => $transaction->fee_amount,
                'amount_paid'       => $transaction->amount_paid,
                'payment_method'    => $transaction->payment_method,
                'payment_reference' => $transaction->payment_reference,
                'receipt_number'    => $transaction->receipt_number,
                'payment_date'      => $transaction->payment_date?->format('F d, Y g:i A'),
                'resident'          => $transaction->resident ? [
                    'name'  => $transaction->resident->name,
                    'email' => $transaction->resident->email,
                    'purok' => $transaction->resident->purok,
                ] : null,
                'document_type'     => $transaction->documentType ? [
                    'name' => $transaction->documentType->name,
                    'code' => $transaction->documentType->code,
                ] : null,
                'payment_verifier'  => $transaction->paymentVerifier ? [
                    'name' => $transaction->paymentVerifier->name,
                ] : null,
                'staff'             => $transaction->staff ? [
                    'name' => $transaction->staff->name,
                ] : null,
            ],
            'barangayName' => \App\Models\SystemSetting::get('barangay_name', 'Barangay Purisima'),
            'municipality' => \App\Models\SystemSetting::get('municipality', 'Municipality of Tago'),
            'province'     => \App\Models\SystemSetting::get('province', 'Province of Surigao del Sur'),
            'printedBy'    => $request->user()?->name ?? 'System',
            'printedAt'    => now()->format('F d, Y g:i A'),
        ]);
    }

    /**
     * Create notification for transaction status change.
     */
    private function createTransactionNotification(Transaction $transaction, string $status): void
    {
        // Ensure relationships are loaded
        if (! $transaction->relationLoaded('documentType')) {
            $transaction->load('documentType');
        }
        if (! $transaction->relationLoaded('resident')) {
            $transaction->load('resident');
        }

        $notificationType = match ($status) {
            'in_progress' => 'transaction_updated',
            'completed' => 'transaction_completed',
            'cancelled' => 'transaction_cancelled',
            'rejected' => 'transaction_rejected',
            default => 'transaction_updated',
        };

        $priority = match ($status) {
            'completed' => 'normal',
            'cancelled', 'rejected' => 'high',
            default => 'normal',
        };

        // Get safe values with null checks
        $documentTypeName = $transaction->documentType?->name ?? 'Unknown Document Type';
        $residentName = $transaction->resident?->name
            ?? data_get($transaction->resident_input_data, '__manual_requestor.full_name', 'Walk-in requestor');

        // Notify all staff members about the transaction update
        $this->notificationService->createNotificationForAllStaff(
            $notificationType,
            "Transaction #{$transaction->id} Updated",
            "Transaction #{$transaction->id} ({$documentTypeName}) for {$residentName} has been {$status}.",
            [
                'transaction_id' => $transaction->id,
                'resident_name' => $residentName,
                'document_type' => $documentTypeName,
                'status' => $status,
            ],
            $priority,
            'transaction'
        );
    }
}
