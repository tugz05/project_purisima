<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MessagingController extends Controller
{
    public function __construct(private MessagingService $messagingService)
    {
        // Middleware is handled in routes
    }

    /**
     * Display a listing of conversations.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);

        $conversations = $search
            ? $this->messagingService->searchConversations($user, $search, $perPage)
            : $this->messagingService->getUserConversations($user, $perPage);

        $unreadCount = $this->messagingService->getUnreadCount($user);

        return Inertia::render('Staff/Messaging/Index', [
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
            'search' => $search,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Show a specific conversation.
     */
    public function show(Conversation $conversation): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to view this conversation.');
        }

        // Mark messages as read
        $this->messagingService->markMessagesAsRead($conversation, $user);

        // Load conversation with relationships
        $conversation->load(['resident', 'staff', 'messages' => function ($query) {
            $query->with('sender')->orderBy('created_at', 'asc')->limit(50);
        }]);

        $unreadCount = $this->messagingService->getUnreadCount($user);

        return Inertia::render('Staff/Messaging/Show', [
            'conversation' => $conversation,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Show conversation data as JSON for AJAX requests.
     */
    public function showAsJson(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (!$conversation->isParticipant($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load conversation with relationships
        $conversation->load(['resident', 'staff', 'messages' => function ($query) {
            $query->with('sender')->orderBy('created_at', 'asc')->limit(50);
        }]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'resident' => $conversation->resident,
                'staff' => $conversation->staff,
                'messages' => $conversation->messages,
                'resident_has_unread' => $conversation->resident_has_unread,
                'staff_has_unread' => $conversation->staff_has_unread,
                'last_message_at' => $conversation->last_message_at,
            ]
        ]);
    }

    /**
     * Mark conversation messages as read for the current user.
     */
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (!$conversation->isParticipant($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $this->messagingService->markMessagesAsRead($conversation, $user);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to send messages in this conversation.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,file'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'], // 10MB max
        ]);

        try {
            $attachments = null;
            if (isset($validated['attachments'])) {
                $attachments = $this->handleFileUploads($validated['attachments']);
            }

            $message = $this->messagingService->sendMessage(
                $conversation,
                $user,
                $validated['content'],
                $validated['type'] ?? 'text',
                $attachments
            );

            // Broadcast the message event for real-time updates
            broadcast(new \App\Events\MessageSent($message, $conversation));

            // Reload conversation with messages
            $conversation->load(['resident', 'staff', 'messages' => function ($query) {
                $query->with('sender')->orderBy('created_at', 'asc')->limit(50);
            }]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'conversation' => [
                    'id' => $conversation->id,
                    'messages' => $conversation->messages,
                    'last_message' => $conversation->last_message,
                    'last_message_at' => $conversation->last_message_at,
                ]
            ]);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Start typing indicator.
     */
    public function startTyping(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to access this conversation.');
        }

        $this->messagingService->startTyping($conversation, $user);

        // Broadcast typing event
        broadcast(new \App\Events\UserTyping($user, $conversation, true));

        return response()->json(['success' => true]);
    }

    /**
     * Stop typing indicator.
     */
    public function stopTyping(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to access this conversation.');
        }

        $this->messagingService->stopTyping($conversation, $user);

        // Broadcast typing event
        broadcast(new \App\Events\UserTyping($user, $conversation, false));

        return response()->json(['success' => true]);
    }

    /**
     * Get typing indicators for a conversation.
     */
    public function getTypingIndicators(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to access this conversation.');
        }

        $typingIndicators = $this->messagingService->getActiveTypingIndicators($conversation, $user);

        return response()->json([
            'typing_indicators' => $typingIndicators,
        ]);
    }

    /**
     * Archive a conversation.
     */
    public function archive(Conversation $conversation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to archive this conversation.');
        }

        $this->messagingService->archiveConversation($conversation, $user);

        return redirect()->route('staff.messaging.index')
            ->with('success', 'Conversation archived successfully.');
    }

    /**
     * Restore an archived conversation.
     */
    public function restore(Conversation $conversation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to restore this conversation.');
        }

        $this->messagingService->restoreConversation($conversation, $user);

        return redirect()->route('staff.messaging.index')
            ->with('success', 'Conversation restored successfully.');
    }

    /**
     * Get unread message count.
     */
    public function getUnreadCount(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = $this->messagingService->getUnreadCount($user);

        return response()->json(['count' => $count]);
    }

    /**
     * Handle file uploads for message attachments.
     */
    private function handleFileUploads(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store('message-attachments', 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        return $attachments;
    }
}
