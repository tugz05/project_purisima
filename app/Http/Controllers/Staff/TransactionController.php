<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffTransactionUpdateRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TransactionService $transactionService,
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'staff_id', 'search', 'sort', 'direction']);
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
        return Inertia::render('Staff/transactions/Show', [
            'transaction' => $transaction->load(['resident', 'staff']),
        ]);
    }

    public function update(StaffTransactionUpdateRequest $request, Transaction $transaction): RedirectResponse
    {
        $oldStatus = $transaction->status;

        $this->transactionService->updateStatus(
            $transaction,
            $request->status,
            $request->user(),
            $request->validated()
        );

        // Create notification for transaction status change
        if ($oldStatus !== $request->status) {
            $this->createTransactionNotification($transaction, $request->status);
        }

        return redirect()->route('staff.transactions.show', $transaction)
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
