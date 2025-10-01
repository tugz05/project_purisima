<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {
        // Middleware is handled in routes
    }

    /**
     * Show the payment processing form
     */
    public function show(Transaction $transaction): Response
    {
        $transaction->load(['resident', 'documentType', 'paymentVerifier']);

        return Inertia::render('Staff/transactions/Payment', [
            'transaction' => $transaction,
            'paymentMethods' => [
                'cash' => 'Cash',
                'gcash' => 'GCash',
                'paymaya' => 'PayMaya',
                'bank_transfer' => 'Bank Transfer',
                'check' => 'Check',
            ],
        ]);
    }

    /**
     * Process a payment
     */
    public function process(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'gcash', 'paymaya', 'bank_transfer', 'check'])],
            'amount_paid' => ['required', 'numeric', 'min:' . $transaction->fee_amount],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'array'],
            'payment_proof.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB max
        ]);

        try {
            $this->paymentService->processPayment(
                $transaction,
                $request->all(),
                $request->user()
            );

            // Create notification for payment completion
            $this->createPaymentNotification($transaction, 'payment_completed');

            return redirect()->route('staff.transactions.show', $transaction)
                ->with('success', 'Payment processed successfully. Receipt number: ' . $transaction->fresh()->receipt_number);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Mark payment as failed
     */
    public function markFailed(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->paymentService->markPaymentFailed(
                $transaction,
                $request->reason,
                $request->user()
            );

            // Create notification for payment failure
            $this->createPaymentNotification($transaction, 'payment_failed');

            return redirect()->route('staff.transactions.show', $transaction)
                ->with('success', 'Payment marked as failed.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Process a refund
     */
    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->paymentService->processRefund(
                $transaction,
                $request->all(),
                $request->user()
            );

            // Create notification for refund
            $this->createPaymentNotification($transaction, 'payment_refunded');

            return redirect()->route('staff.transactions.show', $transaction)
                ->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reset payment status
     */
    public function reset(Transaction $transaction): RedirectResponse
    {
        try {
            $this->paymentService->resetPaymentStatus(
                $transaction,
                request()->user()
            );

            return redirect()->route('staff.transactions.show', $transaction)
                ->with('success', 'Payment status reset to pending.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show payment statistics
     */
    public function statistics(): Response
    {
        $statistics = $this->paymentService->getPaymentStatistics();
        $paymentMethodStats = $this->paymentService->getPaymentMethodStatistics();

        return Inertia::render('Staff/payments/Statistics', [
            'statistics' => $statistics,
            'paymentMethodStats' => $paymentMethodStats,
        ]);
    }

    /**
     * Create notification for payment events
     */
    private function createPaymentNotification(Transaction $transaction, string $type): void
    {
        $notificationData = [
            'transaction_id' => $transaction->id,
            'resident_name' => $transaction->resident->name,
            'document_type' => $transaction->documentType->name ?? 'Unknown',
            'amount' => $transaction->amount_paid ?? $transaction->fee_amount,
        ];

        $notificationType = match($type) {
            'payment_completed' => 'payment_completed',
            'payment_failed' => 'payment_failed',
            'payment_refunded' => 'payment_refunded',
            default => 'payment_updated',
        };

        $title = match($type) {
            'payment_completed' => 'Payment Completed',
            'payment_failed' => 'Payment Failed',
            'payment_refunded' => 'Payment Refunded',
            default => 'Payment Updated',
        };

        $message = match($type) {
            'payment_completed' => "Payment of ₱{$transaction->amount_paid} for {$transaction->resident->name} has been completed.",
            'payment_failed' => "Payment for {$transaction->resident->name} has been marked as failed.",
            'payment_refunded' => "Refund has been processed for {$transaction->resident->name}.",
            default => "Payment status updated for {$transaction->resident->name}.",
        };

        // Notify all staff members
        $this->notificationService->createNotificationForAllStaff(
            $notificationType,
            $title,
            $message,
            $notificationData,
            'normal',
            'transaction'
        );
    }
}
