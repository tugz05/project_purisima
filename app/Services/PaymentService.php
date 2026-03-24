<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PendingFileUploadService $pendingFileUploadService
    ) {}

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
            if (isset($paymentData['payment_proof_upload_ids']) && is_array($paymentData['payment_proof_upload_ids'])) {
                $cleanIds = array_values(array_filter(
                    $paymentData['payment_proof_upload_ids'],
                    fn ($id) => is_string($id) && $id !== ''
                ));
                if ($cleanIds !== []) {
                    $metas = $this->pendingFileUploadService->consumeIds(
                        $staffMember,
                        $cleanIds,
                        PendingFileUploadService::PURPOSE_PAYMENT_PROOF,
                        'payment-proofs'
                    );
                    foreach ($metas as $meta) {
                        $paymentProof[] = [
                            'name' => $meta['name'],
                            'path' => $meta['path'],
                            'size' => $meta['size'],
                            'mime_type' => $meta['mime_type'],
                            'uploaded_at' => Carbon::now(),
                        ];
                    }
                }
            }

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
        return DB::transaction(function () use ($transaction) {
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
        $lastReceipt = Transaction::where('receipt_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Paginated payment-related transactions (fee > 0) for staff audit trail.
     *
     * @param  array{
     *     search: string,
     *     payment_status: string,
     *     payment_method: string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     sort: string,
     *     direction: string,
     *     per_page: int
     * }  $filters
     */
    public function paginatePaymentHistory(array $filters): LengthAwarePaginator
    {
        $query = $this->paymentHistoryBaseQuery();
        $this->applyPaymentHistoryFilters($query, $filters);
        $this->applyPaymentHistorySort($query, $filters);

        return $query->paginate($filters['per_page'])->withQueryString();
    }

    /**
     * Summary counts and totals for the same filter set as the history table.
     *
     * @param  array{
     *     search: string,
     *     payment_status: string,
     *     payment_method: string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     sort: string,
     *     direction: string,
     *     per_page: int
     * }  $filters
     * @return array{
     *     matching_count: int,
     *     paid_count: int,
     *     pending_count: int,
     *     failed_count: int,
     *     refunded_count: int,
     *     collected_total: float|int|string,
     *     outstanding_fees_total: float|int|string
     * }
     */
    public function getPaymentHistorySummary(array $filters): array
    {
        $query = $this->paymentHistoryBaseQuery();
        $this->applyPaymentHistoryFilters($query, $filters);

        $matchingCount = (clone $query)->count();

        $paidCount = (clone $query)->where('payment_status', 'paid')->count();
        $pendingCount = (clone $query)->where('payment_status', 'pending')->count();
        $failedCount = (clone $query)->where('payment_status', 'failed')->count();
        $refundedCount = (clone $query)->where('payment_status', 'refunded')->count();

        $collectedTotal = (clone $query)->where('payment_status', 'paid')->sum('amount_paid');

        $outstandingFeesTotal = (clone $query)
            ->whereIn('payment_status', ['pending', 'failed'])
            ->sum('fee_amount');

        return [
            'matching_count' => $matchingCount,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
            'refunded_count' => $refundedCount,
            'collected_total' => $collectedTotal,
            'outstanding_fees_total' => $outstandingFeesTotal,
        ];
    }

    /**
     * @return Builder<Transaction>
     */
    private function paymentHistoryBaseQuery(): Builder
    {
        return Transaction::query()
            ->with([
                'resident:id,name,email',
                'documentType:id,name,code',
                'paymentVerifier:id,name',
            ])
            ->where('fee_amount', '>', 0);
    }

    /**
     * @param  array{
     *     search: string,
     *     payment_status: string,
     *     payment_method: string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     sort: string,
     *     direction: string,
     *     per_page: int
     * }  $filters
     */
    private function applyPaymentHistoryFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('transaction_id', 'like', '%'.$search.'%')
                    ->orWhere('receipt_number', 'like', '%'.$search.'%')
                    ->orWhere('payment_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('resident', function (Builder $rq) use ($search) {
                        $rq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        if ($filters['payment_method'] !== 'all') {
            $query->where('payment_method', $filters['payment_method']);
        }

        if ($filters['date_from'] !== null) {
            $query->whereRaw('COALESCE(payment_date, transactions.created_at) >= ?', [$filters['date_from'].' 00:00:00']);
        }

        if ($filters['date_to'] !== null) {
            $query->whereRaw('COALESCE(payment_date, transactions.created_at) <= ?', [$filters['date_to'].' 23:59:59']);
        }
    }

    /**
     * @param  array{
     *     search: string,
     *     payment_status: string,
     *     payment_method: string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     sort: string,
     *     direction: string,
     *     per_page: int
     * }  $filters
     */
    private function applyPaymentHistorySort(Builder $query, array $filters): void
    {
        $direction = strtolower($filters['direction']) === 'asc' ? 'asc' : 'desc';

        match ($filters['sort']) {
            'amount_paid' => $query->orderByRaw('amount_paid IS NULL')->orderBy('amount_paid', $direction)->orderByDesc('transactions.id'),
            'fee_amount' => $query->orderBy('fee_amount', $direction)->orderByDesc('transactions.id'),
            'receipt_number' => $query->orderByRaw('receipt_number IS NULL')->orderBy('receipt_number', $direction)->orderByDesc('transactions.id'),
            default => $query->orderByRaw('COALESCE(payment_date, transactions.created_at) '.$direction)->orderByDesc('transactions.id'),
        };
    }
}
