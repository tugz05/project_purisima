<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messaging\SendMessageRequest;
use App\Http\Requests\Staff\SearchStaffMessagingUsersRequest;
use App\Http\Requests\Staff\StartStaffMessagingConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
                'photo_url' => $user->photo_url,
            ],
        ]);
    }

    /**
     * Search residents to start or open a direct conversation.
     */
    public function searchUsers(SearchStaffMessagingUsersRequest $request): JsonResponse
    {
        $q = $request->searchQuery()['q'];

        $users = $this->messagingService->searchResidentsForStaffMessaging($q, 15);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'photo_url' => $u->photo_url,
            ])->values()->all(),
        ]);
    }

    /**
     * Get or create a conversation with a resident and optionally send the first message.
     */
    public function startConversation(StartStaffMessagingConversationRequest $request): JsonResponse
    {
        /** @var \App\Models\User $staff */
        $staff = Auth::user();

        $validated = $request->validated();
        $resident = User::query()->findOrFail($validated['resident_id']);

        $content = isset($validated['content']) ? trim((string) $validated['content']) : '';
        $content = $content === '' ? null : $content;

        try {
            $conversation = $this->messagingService->getOrCreateConversation(
                $resident,
                $staff,
                'Direct Chat'
            );

            if ($content !== null) {
                $this->messagingService->sendMessage($conversation, $staff, $content);
            }

            $conversation->load(['resident', 'staff', 'latestMessages' => function ($query) {
                $query->limit(1)->with('sender');
            }]);

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'conversation' => 'Failed to start conversation: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Show a specific conversation.
     */
    public function show(Conversation $conversation): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (! $conversation->isParticipant($user)) {
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
        if (! $conversation->isParticipant($user)) {
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
            ],
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
        if (! $conversation->isParticipant($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $this->messagingService->markMessagesAsRead($conversation, $user);
            $conversation->refresh();

            return response()->json([
                'success' => true,
                'unread_total' => $this->messagingService->getUnreadCount($user),
                'conversation' => [
                    'id' => $conversation->id,
                    'staff_has_unread' => $conversation->staff_has_unread,
                    'resident_has_unread' => $conversation->resident_has_unread,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Unable to mark messages as read.'], 500);
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure user is a participant
        if (! $conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to send messages in this conversation.');
        }

        try {
            $content = $request->messagingContent();
            $uploadedFiles = $request->messagingFiles();
            $attachments = count($uploadedFiles) > 0 ? $this->handleFileUploads($uploadedFiles) : null;

            $typeInput = $request->input('type');
            $type = is_string($typeInput) && in_array($typeInput, ['text', 'image', 'file'], true)
                ? $typeInput
                : 'text';
            if ($type === 'text' && $attachments !== null) {
                $allImages = collect($attachments)->every(fn (array $a) => str_starts_with((string) ($a['mime_type'] ?? ''), 'image/'));
                $type = $allImages ? 'image' : 'file';
            }

            $message = $this->messagingService->sendMessage(
                $conversation,
                $user,
                $content,
                $type,
                $attachments
            );

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
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'message' => 'Failed to send message: '.$e->getMessage(),
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

        if (! $conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to access this conversation.');
        }

        $this->messagingService->startTyping($conversation, $user);

        return response()->json(['success' => true]);
    }

    /**
     * Stop typing indicator.
     */
    public function stopTyping(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to access this conversation.');
        }

        $this->messagingService->stopTyping($conversation, $user);

        return response()->json(['success' => true]);
    }

    /**
     * Get typing indicators for a conversation.
     */
    public function getTypingIndicators(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $conversation->isParticipant($user)) {
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

        if (! $conversation->isParticipant($user)) {
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

        if (! $conversation->isParticipant($user)) {
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
