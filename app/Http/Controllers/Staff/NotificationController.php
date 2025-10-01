<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = $this->notificationService->getUserNotifications($user, 20);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return Inertia::render('staff/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Get unread notifications for the notification dropdown.
     */
    public function unread(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $unreadNotifications = $this->notificationService->getUnreadNotifications($user, 10);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $unreadNotifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure all notifications belong to the authenticated user
        $notifications = Notification::whereIn('id', $request->notification_ids)
            ->where('user_id', $user->id)
            ->get();

        if ($notifications->count() !== count($request->notification_ids)) {
            abort(403);
        }

        $this->notificationService->markMultipleAsRead($request->notification_ids);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->markAllAsRead($user);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $this->notificationService->deleteNotification($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Delete multiple notifications.
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure all notifications belong to the authenticated user
        $notifications = Notification::whereIn('id', $request->notification_ids)
            ->where('user_id', $user->id)
            ->get();

        if ($notifications->count() !== count($request->notification_ids)) {
            abort(403);
        }

        $this->notificationService->deleteMultipleNotifications($request->notification_ids);

        return response()->json(['success' => true]);
    }

    /**
     * Delete all notifications.
     */
    public function deleteAll()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->deleteAllNotifications($user);

        return response()->json(['success' => true]);
    }

    /**
     * Get notification count for the header.
     */
    public function count()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json(['count' => $unreadCount]);
    }
}
