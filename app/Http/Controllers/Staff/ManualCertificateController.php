<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ManualCertificateFinalizeRequest;
use App\Http\Requests\Staff\ManualCertificateGenerateAiRequest;
use App\Http\Requests\Staff\ManualCertificateLoadTemplateRequest;
use App\Http\Requests\Staff\ManualCertificatePrintRequest;
use App\Models\DocumentType;
use App\Services\AIDocumentGeneratorService;
use App\Services\CertificateVerificationService;
use App\Services\ManualCertificateWizardService;
use App\Services\NotificationService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ManualCertificateController extends Controller
{
    public function __construct(
        private AIDocumentGeneratorService $aiDocumentGeneratorService,
        private ManualCertificateWizardService $wizardService,
        private NotificationService $notificationService,
        private TransactionService $transactionService,
        private CertificateVerificationService $certificateVerificationService
    ) {}

    public function index(): Response
    {
        $documentTypes = DocumentType::query()
            ->active()
            ->ordered()
            ->get(['id', 'code', 'name', 'description']);

        return Inertia::render('Staff/certificates/ManualCertificate', [
            'documentTypes' => $documentTypes,
        ]);
    }

    public function schema(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
        ]);

        $documentType = DocumentType::query()->active()->find($validated['document_type_id']);
        if (! $documentType) {
            return response()->json(['error' => 'Document type not found or inactive.'], 404);
        }

        $layout = $this->wizardService->resolvePrintLayout($documentType);
        $fields = $this->wizardService->fieldDefinitionsForDocumentType($documentType);

        return response()->json([
            'document_type' => [
                'id' => $documentType->id,
                'code' => $documentType->code,
                'name' => $documentType->name,
                'description' => $documentType->description,
            ],
            'print_layout' => $layout,
            'fields_source' => $this->wizardService->additionalStaffFieldCount($documentType) > 0 ? 'layout_with_extras' : 'layout_only',
            'extra_field_count' => $this->wizardService->additionalStaffFieldCount($documentType),
            'template' => [
                'id' => null,
                'name' => $this->wizardService->layoutDisplayName($layout),
            ],
            'fields' => $fields,
        ]);
    }

    public function loadTemplate(ManualCertificateLoadTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $layout = $this->wizardService->resolvePrintLayout($documentType);
        $merge = $this->wizardService->buildMergeDataForDocumentType($documentType, $data['field_values'] ?? []);
        $content = $this->wizardService->buildStarterHtmlForDocumentType($documentType, $merge);

        return response()->json([
            'content' => $content,
            'template_name' => $this->wizardService->layoutDisplayName($layout),
        ]);
    }

    public function generateAi(ManualCertificateGenerateAiRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $documentType = DocumentType::findOrFail($data['document_type_id']);
            $merge = $this->wizardService->buildMergeDataForDocumentType($documentType, $data['field_values'] ?? []);
            $starter = $this->wizardService->buildStarterHtmlForDocumentType($documentType, $merge);

            $editorHint = trim((string) ($data['current_content'] ?? ''));
            $reference = $editorHint !== '' ? $editorHint : $starter;

            $documentTypePayload = [
                'name' => $documentType->name,
                'description' => $documentType->description,
            ];
            $residentData = $this->wizardService->walkInResidentDataForAi($merge);
            $requestData = $this->wizardService->filterFieldValuesForDocumentType($documentType, $data['field_values'] ?? []);
            $inputDefinitions = $this->wizardService->inputFieldDefinitionsForAi($documentType);

            $content = $this->aiDocumentGeneratorService->generateDocumentContent(
                $documentTypePayload,
                $residentData,
                $requestData,
                [],
                $inputDefinitions,
                $reference,
            );

            return response()->json([
                'content' => $content,
                'success' => true,
                'generated_by' => 'AI (same engine as transaction certificates)',
            ]);
        } catch (\Exception $e) {
            Log::error('Manual certificate AI failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'AI generation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function print(ManualCertificatePrintRequest $request): Response
    {
        $documentType = DocumentType::findOrFail($request->validated('document_type_id'));
        $printLayout = $this->wizardService->resolvePrintLayout($documentType);

        return Inertia::render('Staff/transactions/PrintCertificate', [
            'transaction' => null,
            'resident' => null,
            'documentTypeName' => $documentType->name,
            'printLayout' => $printLayout,
            'content' => $request->validated('content'),
            'currentDate' => now()->format('F d, Y'),
            'currentDateFormatted' => now()->format('jS \d\a\y \o\f F, Y'),
            'officerOfTheDay' => $request->validated('officer_of_the_day'),
            'verificationUrl' => null,
            'previewQrUrl' => URL::route('certificate.verify.about'),
        ]);
    }

    /**
     * Persist the walk-in certificate as a transaction after staff finish editing the body.
     */
    public function finalize(ManualCertificateFinalizeRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $documentType = DocumentType::findOrFail($validated['document_type_id']);
            $sanitizedFields = $this->wizardService->filterFieldValuesForDocumentType($documentType, $validated['field_values'] ?? []);

            $manualFullName = trim((string) ($validated['manual_full_name'] ?? ''));
            if ($manualFullName === '') {
                $manualFullName = $this->wizardService->resolveRequestorDisplayName($sanitizedFields);
            }

            $manualAddress = trim((string) ($validated['manual_address'] ?? ''));
            if ($manualAddress === '') {
                $manualAddress = trim((string) ($sanitizedFields['address'] ?? ''));
            }

            $title = trim((string) ($validated['title'] ?? ''));
            if ($title === '') {
                $title = $documentType->name.' – '.$manualFullName;
            }

            $feeAmount = $validated['fee_amount'] ?? null;
            $fee = $feeAmount !== null && $feeAmount !== ''
                ? (float) $feeAmount
                : (float) ($documentType->fee_amount ?? 0);

            $payload = [
                'manual_full_name' => $manualFullName,
                'manual_email' => trim((string) ($validated['manual_email'] ?? '')),
                'manual_phone' => trim((string) ($validated['manual_phone'] ?? '')),
                'manual_purok' => trim((string) ($validated['manual_purok'] ?? '')),
                'manual_address' => $manualAddress,
                'type' => $documentType->code,
                'title' => $title,
                'required_documents' => [],
                'required_fields' => $sanitizedFields,
                'fee_amount' => $fee,
                'staff_notes' => trim((string) ($validated['staff_notes'] ?? '')),
            ];

            $transaction = $this->transactionService->createManualEntryByStaff($payload, $request->user());

            $officer = $validated['officer_of_the_day'] ?? null;
            $officer = is_string($officer) ? trim($officer) : '';
            $officer = $officer !== '' ? $officer : null;

            $this->transactionService->updateStatus(
                $transaction->fresh(),
                'in_progress',
                $request->user(),
                [
                    'officer_of_the_day' => $officer,
                    'document_content' => $validated['document_content'],
                ]
            );

            $transaction = $transaction->fresh();
            $this->certificateVerificationService->ensureVerificationToken($transaction);
            $transaction = $transaction->fresh();
            $transaction->load('documentType');
            $this->notificationService->createNotificationForAllStaff(
                'transaction_created',
                'Walk-in certificate saved as transaction',
                "Staff saved {$documentType->name} for {$payload['manual_full_name']} from the walk-in certificate wizard (transaction #{$transaction->id}).",
                [
                    'transaction_id' => $transaction->id,
                    'resident_name' => $payload['manual_full_name'],
                    'document_type' => $documentType->name,
                ],
                'normal',
                'transaction'
            );

            return redirect()->route('staff.transactions.show', $transaction)
                ->with('success', 'Walk-in certificate saved as a transaction. You can keep editing or print from the transaction page.');
        } catch (\Exception $e) {
            Log::error('Manual certificate finalize failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
