<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = $this->notificationService->getUserNotifications($user, 20);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return Inertia::render('resident/notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function unread(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return response()->json([
            'notifications' => $this->notificationService->getUnreadNotifications($user, 10),
            'unreadCount' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->notificationService->markAllAsRead($user);

        return response()->json(['success' => true]);
    }

    public function destroy(Notification $notification)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $this->notificationService->deleteNotification($notification);

        return response()->json(['success' => true]);
    }

    public function count()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return response()->json(['count' => $this->notificationService->getUnreadCount($user)]);
    }
}

