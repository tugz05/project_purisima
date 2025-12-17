<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
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
        $filters = $request->only(['status', 'type']);
        $transactions = $this->transactionService->getResidentTransactions($request->user(), $filters);
        $transactionTypes = $this->transactionService->getTransactionTypes();

        return Inertia::render('resident/transactions/Index', [
            'transactions' => $transactions,
            'transactionTypes' => $transactionTypes,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $transactionTypes = $this->transactionService->getTransactionTypes();

        return Inertia::render('resident/transactions/Create', [
            'transactionTypes' => $transactionTypes,
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        try {
            $transaction = $this->transactionService->create($request->validated(), $request->user());

            // Load document type relationship
            $transaction->load('documentType');

            // Create notification for all staff members about new transaction
            $this->notificationService->createNotificationForAllStaff(
                'transaction_created',
                'New Transaction Request',
                "New {$transaction->documentType->name} request from {$transaction->resident->name}",
                [
                    'transaction_id' => $transaction->id,
                    'resident_name' => $transaction->resident->name,
                    'document_type' => $transaction->documentType->name,
                ],
                'normal',
                'transaction'
            );

            return redirect()->route('resident.transactions.index')
                ->with('success', 'Transaction submitted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        return Inertia::render('resident/transactions/Show', [
            'transaction' => $transaction->load(['staff']),
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $transactionTypes = $this->transactionService->getTransactionTypes();

        return Inertia::render('resident/transactions/Edit', [
            'transaction' => $transaction,
            'transactionTypes' => $transactionTypes,
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $transaction->update($request->validated());

        return redirect()->route('resident.transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return redirect()->route('resident.transactions.index')
            ->with('success', 'Transaction cancelled successfully.');
    }
}
