<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Process a payment for a transaction
     */
    public function processPayment(
        Transaction $transaction,
        array $paymentData,
        User $staffMember
    ): Transaction {
        return DB::transaction(function () use ($transaction, $paymentData, $staffMember) {
            // Validate payment amount
            if ($paymentData['amount_paid'] < $transaction->fee_amount) {
                throw new \Exception('Payment amount is less than required fee.');
            }

            // Handle payment proof uploads
            $paymentProof = [];
            if (isset($paymentData['payment_proof']) && is_array($paymentData['payment_proof'])) {
                foreach ($paymentData['payment_proof'] as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $path = $file->store('payment-proofs', 'public');
                        $paymentProof[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'uploaded_at' => Carbon::now(),
                        ];
                    }
                }
            }

            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            // Update transaction with payment details
            $transaction->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentData['payment_method'],
                'amount_paid' => $paymentData['amount_paid'],
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'payment_notes' => $paymentData['payment_notes'] ?? null,
                'payment_date' => Carbon::now(),
                'payment_verified_at' => Carbon::now(),
                'payment_verified_by' => $staffMember->id,
                'receipt_number' => $receiptNumber,
                'payment_proof' => $paymentProof,
                'fee_paid' => true,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentFailed(
        Transaction $transaction,
        string $reason,
        User $staffMember
    ): Transaction {
        return DB::transaction(function () use ($transaction, $reason, $staffMember) {
            $transaction->update([
                'payment_status' => 'failed',
                'payment_notes' => $reason,
                'payment_verified_at' => Carbon::now(),
                'payment_verified_by' => $staffMember->id,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Process a refund
     */
    public function processRefund(
        Transaction $transaction,
        array $refundData,
        User $staffMember
    ): Transaction {
        return DB::transaction(function () use ($transaction, $refundData, $staffMember) {
            $transaction->update([
                'payment_status' => 'refunded',
                'payment_notes' => $refundData['reason'] ?? 'Refund processed',
                'payment_verified_at' => Carbon::now(),
                'payment_verified_by' => $staffMember->id,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Reset payment status to pending
     */
    public function resetPaymentStatus(
        Transaction $transaction,
        User $staffMember
    ): Transaction {
        return DB::transaction(function () use ($transaction, $staffMember) {
            $transaction->update([
                'payment_status' => 'pending',
                'payment_method' => null,
                'amount_paid' => null,
                'payment_reference' => null,
                'payment_notes' => null,
                'payment_date' => null,
                'payment_verified_at' => null,
                'payment_verified_by' => null,
                'receipt_number' => null,
                'payment_proof' => null,
                'fee_paid' => false,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Generate a unique receipt number
     */
    private function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $year = date('Y');
        $month = date('m');

        // Get the last receipt number for this month
        $lastReceipt = Transaction::where('receipt_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(): array
    {
        $totalTransactions = Transaction::count();
        $paidTransactions = Transaction::where('payment_status', 'paid')->count();
        $pendingPayments = Transaction::where('payment_status', 'pending')->count();
        $failedPayments = Transaction::where('payment_status', 'failed')->count();
        $refundedPayments = Transaction::where('payment_status', 'refunded')->count();

        $totalRevenue = Transaction::where('payment_status', 'paid')->sum('amount_paid');
        $pendingRevenue = Transaction::where('payment_status', 'pending')->sum('fee_amount');

        return [
            'total_transactions' => $totalTransactions,
            'paid_transactions' => $paidTransactions,
            'pending_payments' => $pendingPayments,
            'failed_payments' => $failedPayments,
            'refunded_payments' => $refundedPayments,
            'total_revenue' => $totalRevenue,
            'pending_revenue' => $pendingRevenue,
            'payment_completion_rate' => $totalTransactions > 0 ? round(($paidTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }

    /**
     * Get payment methods statistics
     */
    public function getPaymentMethodStatistics(): array
    {
        return Transaction::where('payment_status', 'paid')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount_paid) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->payment_method => [
                        'count' => $item->count,
                        'total_amount' => $item->total_amount,
                        'percentage' => 0, // Will be calculated in the frontend
                    ]
                ];
            })
            ->toArray();
    }
}
