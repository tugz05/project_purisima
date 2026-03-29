<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Str;

class CertificateVerificationService
{
    /**
     * Ensure the transaction has a stable public verification token stored in generated_document_data.
     */
    public function ensureVerificationToken(Transaction $transaction): string
    {
        $data = $transaction->generated_document_data ?? [];
        $existing = $data['verification_token'] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $token = (string) Str::uuid();
        $data['verification_token'] = $token;
        $transaction->forceFill(['generated_document_data' => $data])->save();

        return $token;
    }

    public function findTransactionByVerificationToken(string $token): ?Transaction
    {
        if ($token === '' || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
            return null;
        }

        return Transaction::query()
            ->where('generated_document_data->verification_token', $token)
            ->with(['documentType', 'resident'])
            ->first();
    }
}
