<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BroadcastHelper;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a notification for a user.
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'normal',
        string $category = 'transaction'
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'category' => $category,
        ]);

        BroadcastHelper::safeBroadcast(new NotificationCreated($notification));

        return $notification;
    }

    /**
     * Create a notification for all staff members.
     */
    public function createNotificationForAllStaff(
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'normal',
        string $category = 'transaction'
    ): void {
        $staffUsers = User::where('role', 'staff')->get();

        foreach ($staffUsers as $user) {
            $this->createNotification($user, $type, $title, $message, $data, $priority, $category);
        }
    }

    /**
     * Create a transaction notification.
     */
    public function createTransactionNotification(
        User $user,
        string $type,
        string $transactionId,
        string $residentName,
        string $documentType,
        string $priority = 'normal'
    ): Notification {
        $title = match($type) {
            'transaction_created' => 'New Transaction Request',
            'transaction_updated' => 'Transaction Updated',
            'transaction_completed' => 'Transaction Completed',
            'transaction_cancelled' => 'Transaction Cancelled',
            'transaction_rejected' => 'Transaction Rejected',
            default => 'Transaction Notification',
        };

        $message = match($type) {
            'transaction_created' => "New {$documentType} request from {$residentName}",
            'transaction_updated' => "Transaction #{$transactionId} has been updated",
            'transaction_completed' => "Transaction #{$transactionId} has been completed",
            'transaction_cancelled' => "Transaction #{$transactionId} has been cancelled",
            'transaction_rejected' => "Transaction #{$transactionId} has been rejected",
            default => "Transaction #{$transactionId} notification",
        };

        return $this->createNotification(
            $user,
            $type,
            $title,
            $message,
            [
                'transaction_id' => $transactionId,
                'resident_name' => $residentName,
                'document_type' => $documentType,
            ],
            $priority,
            'transaction'
        );
    }

    /**
     * Notify a resident about a change to their own transaction.
     * Uses plain, resident-friendly language.
     */
    public function notifyResidentAboutTransaction(
        Transaction $transaction,
        string $status
    ): void {
        $transaction->loadMissing(['resident', 'documentType']);

        /** @var User|null $resident */
        $resident = $transaction->resident instanceof User ? $transaction->resident : null;
        if (! $resident) {
            return;
        }

        $doc = $transaction->documentType?->name ?? 'document';
        $txId = $transaction->id;

        [$type, $title, $message, $priority] = match ($status) {
            'in_progress' => [
                'transaction_updated',
                'Request In Progress',
                "Your {$doc} request (#{$txId}) is now being processed.",
                'normal',
            ],
            'completed' => [
                'transaction_completed',
                'Request Completed',
                "Your {$doc} request (#{$txId}) has been completed. Please visit the barangay office to claim it.",
                'normal',
            ],
            'rejected' => [
                'transaction_rejected',
                'Request Rejected',
                "Your {$doc} request (#{$txId}) was rejected." .
                    ($transaction->rejection_reason ? " Reason: {$transaction->rejection_reason}" : ''),
                'high',
            ],
            'cancelled' => [
                'transaction_cancelled',
                'Request Cancelled',
                "Your {$doc} request (#{$txId}) has been cancelled.",
                'normal',
            ],
            default => [
                'transaction_updated',
                'Transaction Updated',
                "Your {$doc} request (#{$txId}) has been updated.",
                'normal',
            ],
        };

        $this->createNotification(
            $resident,
            $type,
            $title,
            $message,
            ['transaction_id' => $txId, 'document_type' => $doc, 'status' => $status],
            $priority,
            'transaction'
        );
    }

    /**
     * Notify a resident about a payment event on their transaction.
     */
    public function notifyResidentAboutPayment(Transaction $transaction, string $type): void
    {
        $transaction->loadMissing(['resident', 'documentType']);

        /** @var User|null $resident */
        $resident = $transaction->resident instanceof User ? $transaction->resident : null;
        if (! $resident) {
            return;
        }

        $doc = $transaction->documentType?->name ?? 'document';
        $txId = $transaction->id;
        $amount = number_format((float) ($transaction->amount_paid ?? $transaction->fee_amount), 2);

        [$notifType, $title, $message, $priority] = match ($type) {
            'payment_completed' => [
                'payment_completed',
                'Payment Confirmed',
                "Your payment of ₱{$amount} for {$doc} (#{$txId}) has been confirmed.",
                'normal',
            ],
            'payment_failed' => [
                'payment_failed',
                'Payment Issue',
                "There was an issue with your payment for {$doc} (#{$txId}). Please contact the barangay office.",
                'high',
            ],
            default => [
                'payment_updated',
                'Payment Update',
                "Payment status updated for your {$doc} (#{$txId}).",
                'normal',
            ],
        };

        $this->createNotification(
            $resident,
            $notifType,
            $title,
            $message,
            ['transaction_id' => $txId, 'document_type' => $doc, 'type' => $type, 'amount' => $amount],
            $priority,
            'transaction'
        );
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markMultipleAsRead(array $notificationIds): void
    {
        Notification::whereIn('id', $notificationIds)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete notification.
     */
    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Delete multiple notifications.
     */
    public function deleteMultipleNotifications(array $notificationIds): void
    {
        Notification::whereIn('id', $notificationIds)->delete();
    }

    /**
     * Delete all notifications for a user.
     */
    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Get notifications for a user with pagination.
     */
    public function getUserNotifications(User $user, int $perPage = 20)
    {
        $paginator = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (Notification $n) {
            $n->append('time_ago');
            return $n;
        });

        return $paginator;
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(User $user, int $limit = 10)
    {
        $rows = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $rows->each->append('time_ago');

        return $rows;
    }

    /**
     * Clean up old notifications (older than 30 days).
     */
    public function cleanupOldNotifications(): void
    {
        Notification::where('created_at', '<', now()->subDays(30))->delete();
    }
}
