<?php

namespace App\Http\Controllers\Resident;

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

        // Get recent conversations for floating chat (limit conversations, but NOT messages)
        $conversations = $user->residentConversations()
            ->active()
            ->with(['staff', 'messages' => function ($q) {
                // Show full message history in ascending order for clarity
                $q->oldest()->with('sender');
            }])
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        $unreadCount = $this->messagingService->getUnreadCount($user);

        // Format conversations for floating chat
        $formattedConversations = $conversations->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'staff_name' => $conversation->staff->name,
                'messages' => $conversation->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'created_at' => $message->created_at,
                        'is_read' => $message->is_read,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                        ]
                    ];
                }),
                'unread_count' => $conversation->resident_has_unread ? 1 : 0,
                'is_active' => $conversation->is_active,
            ];
        });

        return Inertia::render('resident/floating-chat/Index', [
            'conversations' => $formattedConversations,
            'currentUser' => $user,
        ]);
    }

    /**
     * Create a new conversation with staff (API endpoint for floating chat).
     */
    public function createConversation(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'content' => ['nullable', 'string', 'max:5000'],
        ]);

        $staff = User::findOrFail($validated['staff_id']);

        // Ensure staff member has staff or admin role
        if (!in_array($staff->role, ['staff', 'admin'])) {
            throw ValidationException::withMessages([
                'staff_id' => 'Selected user is not a staff member.',
            ]);
        }

        try {
            $conversation = $this->messagingService->getOrCreateConversation(
                $user,
                $staff,
                'Direct Chat'
            );

            $message = null;
            if ($validated['content']) {
                $message = $this->messagingService->sendMessage(
                    $conversation,
                    $user,
                    $validated['content']
                );
            }

            return response()->json([
                'conversation' => $conversation->load(['staff', 'messages' => function ($q) {
                    $q->oldest()->with('sender');
                }]),
                'message' => $message
            ]);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'conversation' => 'Failed to create conversation: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a general conversation with first available staff member.
     */
    public function createGeneralConversation(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Debug: Log the user info
        \Illuminate\Support\Facades\Log::info('createGeneralConversation called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_email' => $user->email
        ]);

        // Get any available staff member (simple approach)
        $staff = User::whereIn('role', ['staff', 'admin'])->first();

        \Illuminate\Support\Facades\Log::info('Staff lookup result', [
            'staff_found' => $staff ? true : false,
            'staff_id' => $staff?->id,
            'staff_role' => $staff?->role
        ]);

        if (!$staff) {
            \Illuminate\Support\Facades\Log::warning('No staff members available');
            return response()->json([
                'error' => 'No staff members are available at the moment'
            ], 503);
        }

        $validated = [
            'staff_id' => $staff->id,
            'content' => request('content', 'Hello, I need assistance.'),
        ];

        try {
            \Illuminate\Support\Facades\Log::info('Creating conversation', $validated);

            $conversation = $this->messagingService->getOrCreateConversation(
                $user,
                $staff,
                'General Support'
            );

            \Illuminate\Support\Facades\Log::info('Conversation created', [
                'conversation_id' => $conversation->id,
                'conversation_exists' => $conversation->exists,
                'resident_id' => $conversation->resident_id,
                'staff_id' => $conversation->staff_id
            ]);

            // Send initial message if provided
            $message = null;
            if ($validated['content'] && $validated['content'] !== 'Hello, I need assistance.') {
                $message = $this->messagingService->sendMessage(
                    $conversation,
                    $user,
                    $validated['content']
                );
                \Illuminate\Support\Facades\Log::info('Initial message sent', ['message_id' => $message->id]);
            }

            // Format for floating chat - load ALL messages in ascending order
            $conversation->load(['staff', 'messages' => function ($q) {
                $q->oldest()->with('sender');
            }]);

            $formattedConversation = [
                'id' => $conversation->id,
                'staff_name' => $conversation->staff->name,
                'messages' => $conversation->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'created_at' => $message->created_at,
                        'is_read' => $message->is_read,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                        ]
                    ];
                }),
                'unread_count' => 0,
                'is_active' => $conversation->is_active,
            ];

            return response()->json([
                'success' => true,
                'conversation' => $formattedConversation,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
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

        return Inertia::render('Resident/Messaging/Show', [
            'conversation' => $conversation,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Show a specific conversation as JSON (for polling fallback in floating chat).
     */
    public function showAsJson(Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$conversation->isParticipant($user)) {
            abort(403, 'You are not authorized to view this conversation.');
        }

        $conversation->load(['staff', 'messages' => function ($query) {
            $query->with('sender')->orderBy('created_at', 'asc');
        }]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'staff_name' => $conversation->staff?->name,
                'messages' => $conversation->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'created_at' => $message->created_at,
                        'is_read' => $message->is_read,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                        ],
                    ];
                }),
                'unread_count' => $conversation->resident_has_unread ? 1 : 0,
                'is_active' => $conversation->is_active,
            ],
        ]);
    }

    /**
     * Start a new conversation with staff.
     */
    public function create(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get available staff members
        $staffMembers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['staff', 'admin']);
        })->select('id', 'name', 'email')->get();

        return Inertia::render('Resident/Messaging/Create', [
            'staffMembers' => $staffMembers,
        ]);
    }

    /**
     * Store a new conversation.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $staff = User::findOrFail($validated['staff_id']);

        // Ensure staff member has staff or admin role
        if (!in_array($staff->role, ['staff', 'admin'])) {
            throw ValidationException::withMessages([
                'staff_id' => 'Selected user is not a staff member.',
            ]);
        }

        try {
            $conversation = $this->messagingService->getOrCreateConversation(
                $user,
                $staff,
                $validated['subject']
            );

            $this->messagingService->sendMessage(
                $conversation,
                $user,
                $validated['content']
            );

            return redirect()->route('resident.messaging.show', $conversation)
                ->with('success', 'Message sent successfully.');
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Debug: Log conversation info
        \Illuminate\Support\Facades\Log::info('Resident sendMessage called', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'conversation_exists' => $conversation->exists
        ]);

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

            return response()->json([
                'success' => true,
                'message' => $message,
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

        return redirect()->route('resident.messaging.index')
            ->with('success', 'Conversation archived successfully.');
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
