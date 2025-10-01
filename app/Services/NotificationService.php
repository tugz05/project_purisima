<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
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
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'category' => $category,
        ]);
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
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(User $user, int $limit = 10)
    {
        return $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old notifications (older than 30 days).
     */
    public function cleanupOldNotifications(): void
    {
        Notification::where('created_at', '<', now()->subDays(30))->delete();
    }
}
