<?php

namespace App\Http\Controllers;

use App\Services\CertificateVerificationService;
use Inertia\Inertia;
use Inertia\Response;

class CertificateVerificationController extends Controller
{
    public function __construct(
        private CertificateVerificationService $certificateVerificationService
    ) {}

    public function about(): Response
    {
        return Inertia::render('CertificateVerifyAbout');
    }

    public function show(string $token): Response
    {
        $transaction = $this->certificateVerificationService->findTransactionByVerificationToken($token);

        if (! $transaction || ! $transaction->documentType) {
            return Inertia::render('CertificateVerification', [
                'valid' => false,
            ]);
        }

        $residentInput = $transaction->resident_input_data ?? [];
        $manual = $residentInput['__manual_requestor'] ?? [];
        $requestorName = '';
        if (is_array($manual)) {
            $requestorName = trim((string) ($manual['full_name'] ?? ''));
        }
        if ($requestorName === '' && $transaction->resident) {
            $requestorName = trim((string) $transaction->resident->name);
        }

        $issuedAt = $transaction->document_generated_at ?? $transaction->processed_at ?? $transaction->updated_at;

        return Inertia::render('CertificateVerification', [
            'valid' => true,
            'documentTypeName' => $transaction->documentType->name,
            'requestorDisplay' => $requestorName !== '' ? $requestorName : 'On file',
            'status' => $transaction->status,
            'issuedAt' => $issuedAt?->toIso8601String(),
            'referenceLabel' => 'Transaction #'.$transaction->id,
        ]);
    }
}
