<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffTransactionUpdateRequest;
use App\Models\Transaction;
use App\Models\CertificateTemplate;
use App\Services\TransactionService;
use App\Services\CertificateTemplateService;
use App\Services\NotificationService;
use App\Services\AIDocumentGeneratorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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
        private AIDocumentGeneratorService $aiService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'staff_id', 'payment_status', 'search', 'sort', 'direction']);
        $perPage = $request->get('per_page', 15);

        $transactions = $this->transactionService->getStaffTransactions($filters, $perPage);
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
        $oldStatus = $transaction->status;

        // Get validated data and ensure officer_of_the_day is included
        $validated = $request->validated();

        // Explicitly include officer_of_the_day if it's in the request (even if null/empty)
        if ($request->has('officer_of_the_day')) {
            $validated['officer_of_the_day'] = $request->input('officer_of_the_day');
        }

        Log::info('Updating transaction', [
            'transaction_id' => $transaction->id,
            'officer_of_the_day_in_request' => $request->has('officer_of_the_day'),
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
            $this->createTransactionNotification($transaction, $request->status);
        }

        // Return with the updated transaction data
        return redirect()->route('staff.transactions.show', $transaction->fresh())
            ->with('success', 'Transaction updated successfully.');
    }

    public function assign(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->updateStatus(
            $transaction,
            'in_progress',
            $request->user()
        );

        return redirect()->back()
            ->with('success', 'Transaction assigned to you successfully.');
    }

    /**
     * Load template content and process with resident data
     */
    public function loadTemplate(Transaction $transaction, Request $request): JsonResponse
    {
        $templateId = $request->get('template_id');

        if ($templateId) {
            $template = CertificateTemplate::find($templateId);
        } else {
            // Get default template
            $template = $transaction->document_type_id
                ? CertificateTemplate::getDefaultForDocumentType($transaction->document_type_id)
                : null;
        }

        if (!$template) {
            return response()->json([
                'error' => 'No template found for this document type'
            ], 404);
        }

        // Get resident data
        $resident = $transaction->resident;
        $nameParts = explode(' ', $resident->name ?? '');
        $firstName = $nameParts[0] ?? '';
        $lastName = end($nameParts) ?? '';

        $residentData = [
            'name' => $resident->name ?? '',
            'first_name' => $resident->first_name ?? $firstName,
            'middle_name' => $resident->middle_name ?? '',
            'last_name' => $resident->last_name ?? $lastName,
            'email' => $resident->email ?? '',
            'phone' => $resident->phone ?? '',
            'address' => $resident->address ?? '',
            'purok' => $resident->purok ?? '',
            'barangay' => $resident->barangay ?? 'Barangay Purisima',
            'municipality' => $resident->municipality ?? 'Tago',
            'province' => $resident->province ?? 'Surigao del Sur',
            'date' => now()->format('F d, Y'),
            'date_issued' => now()->format('F d, Y'),
            'time' => now()->format('h:i A'),
            'year' => now()->year,
            'month' => now()->format('F'),
            'day' => now()->day,
            'punong_barangay' => 'EMMANUEL P. ISIANG',
            'officer_of_day' => 'Officer of the Day',
        ];

        // Merge with resident input data if available
        if ($transaction->resident_input_data && is_array($transaction->resident_input_data)) {
            $residentData = array_merge($residentData, $transaction->resident_input_data);
        }

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
        try {
            $transaction->load(['resident', 'documentType']);

            if (!$transaction->documentType) {
                return response()->json([
                    'error' => 'Document type not found for this transaction.'
                ], 404);
            }

            // Generate content using AI only - no fallback
            $content = $this->aiService->generateCertificateContent($transaction);

            return response()->json([
                'content' => $content,
                'generated_by' => 'AI (Gemini)',
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Generation Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'AI generation failed: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Print certificate as HTML
     */
    public function printCertificate(Transaction $transaction): Response
    {
        // Refresh the transaction to get the latest data
        $transaction->refresh();
        $transaction->load(['resident', 'documentType', 'staff']);

        if (!$transaction->documentType) {
            abort(404, 'Document type not found for this transaction.');
        }

        $content = $transaction->generated_document_data['content'] ?? '';

        if (empty($content)) {
            abort(404, 'Certificate content not found. Please generate the certificate first.');
        }

        // Get resident data for template
        $resident = $transaction->resident;
        $documentTypeName = $transaction->documentType->name;
        $currentDate = now()->format('F d, Y');
        $currentDateFormatted = now()->format('jS \d\a\y \o\f F, Y');
        $officerOfTheDay = $transaction->officer_of_the_day;

        return Inertia::render('Staff/transactions/PrintCertificate', [
            'transaction' => $transaction,
            'resident' => $resident,
            'documentTypeName' => $documentTypeName,
            'content' => $content,
            'currentDate' => $currentDate,
            'currentDateFormatted' => $currentDateFormatted,
            'officerOfTheDay' => $officerOfTheDay ?? null,
        ]);
    }

    /**
     * Create notification for transaction status change.
     */
    private function createTransactionNotification(Transaction $transaction, string $status): void
    {
        $notificationType = match($status) {
            'in_progress' => 'transaction_updated',
            'completed' => 'transaction_completed',
            'cancelled' => 'transaction_cancelled',
            'rejected' => 'transaction_rejected',
            default => 'transaction_updated',
        };

        $priority = match($status) {
            'completed' => 'normal',
            'cancelled', 'rejected' => 'high',
            default => 'normal',
        };

        // Notify all staff members about the transaction update
        $this->notificationService->createNotificationForAllStaff(
            $notificationType,
            "Transaction #{$transaction->id} Updated",
            "Transaction #{$transaction->id} ({$transaction->documentType->name}) for {$transaction->resident->name} has been {$status}.",
            [
                'transaction_id' => $transaction->id,
                'resident_name' => $transaction->resident->name,
                'document_type' => $transaction->documentType->name,
                'status' => $status,
            ],
            $priority,
            'transaction'
        );
    }
}
