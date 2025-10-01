<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\TypingIndicator;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessagingService
{
    /**
     * Get or create a conversation between resident and staff.
     */
    public function getOrCreateConversation(User $resident, User $staff, string $subject = null): Conversation
    {
        \Illuminate\Support\Facades\Log::info('getOrCreateConversation called', [
            'resident_id' => $resident->id,
            'staff_id' => $staff->id,
            'subject' => $subject
        ]);

        $conversation = Conversation::firstOrCreate(
            [
                'resident_id' => $resident->id,
                'staff_id' => $staff->id,
            ],
            [
                'subject' => $subject,
                'is_active' => true,
            ]
        );

        \Illuminate\Support\Facades\Log::info('Conversation result', [
            'conversation_id' => $conversation->id,
            'was_recently_created' => $conversation->wasRecentlyCreated,
            'exists' => $conversation->exists
        ]);

        return $conversation;
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Conversation $conversation, User $sender, string $content, string $type = 'text', array $attachments = null): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $content, $type, $attachments) {
            // Create the message
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'content' => $content,
                'type' => $type,
                'attachments' => $attachments,
            ]);

            // Update conversation with last message info
            $conversation->update([
                'last_message' => $content,
                'last_message_at' => Carbon::now(),
            ]);

            // Update unread flags
            if ($sender->id === $conversation->resident_id) {
                $conversation->update(['staff_has_unread' => true]);
            } else {
                $conversation->update(['resident_has_unread' => true]);
            }

            // Broadcast the message
            broadcast(new MessageSent($message, $conversation));

            return $message->load('sender');
        });
    }

    /**
     * Get conversations for a user.
     */
    public function getUserConversations(User $user, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Conversation::forUser($user)
            ->active()
            ->with(['resident', 'staff', 'latestMessages' => function ($query) {
                $query->limit(1)->with('sender');
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get messages for a conversation.
     */
    public function getConversationMessages(Conversation $conversation, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Mark messages as read for a user in a conversation.
     */
    public function markMessagesAsRead(Conversation $conversation, User $user): void
    {
        $conversation->markAsReadForUser($user);
    }

    /**
     * Start typing indicator.
     */
    public function startTyping(Conversation $conversation, User $user): void
    {
        TypingIndicator::startTyping($conversation->id, $user->id);

        // Broadcast typing event
        broadcast(new UserTyping($user, $conversation, true));
    }

    /**
     * Stop typing indicator.
     */
    public function stopTyping(Conversation $conversation, User $user): void
    {
        $typingIndicator = TypingIndicator::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if ($typingIndicator) {
            $typingIndicator->stopTyping();

            // Broadcast typing stopped event
            broadcast(new UserTyping($user, $conversation, false));
        }
    }

    /**
     * Get active typing indicators for a conversation.
     */
    public function getActiveTypingIndicators(Conversation $conversation, User $excludeUser = null): Collection
    {
        $excludeUserId = $excludeUser ? $excludeUser->id : null;
        return TypingIndicator::getActiveForConversation($conversation->id, $excludeUserId);
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Conversation::forUser($user)
            ->where(function ($query) use ($user) {
                if ($user->role === 'resident') {
                    $query->where('resident_has_unread', true);
                } else {
                    $query->where('staff_has_unread', true);
                }
            })
            ->count();
    }

    /**
     * Get conversations with unread messages for a user.
     */
    public function getConversationsWithUnread(User $user): Collection
    {
        return Conversation::forUser($user)
            ->active()
            ->where(function ($query) use ($user) {
                if ($user->role === 'resident') {
                    $query->where('resident_has_unread', true);
                } else {
                    $query->where('staff_has_unread', true);
                }
            })
            ->with(['resident', 'staff'])
            ->get();
    }

    /**
     * Archive a conversation.
     */
    public function archiveConversation(Conversation $conversation, User $user): void
    {
        if ($conversation->isParticipant($user)) {
            $conversation->update(['is_active' => false]);
        }
    }

    /**
     * Restore an archived conversation.
     */
    public function restoreConversation(Conversation $conversation, User $user): void
    {
        if ($conversation->isParticipant($user)) {
            $conversation->update(['is_active' => true]);
        }
    }

    /**
     * Delete a conversation (soft delete by archiving).
     */
    public function deleteConversation(Conversation $conversation, User $user): void
    {
        $this->archiveConversation($conversation, $user);
    }

    /**
     * Search conversations for a user.
     */
    public function searchConversations(User $user, string $query, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Conversation::forUser($user)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', '%' . $query . '%')
                  ->orWhere('last_message', 'like', '%' . $query . '%')
                  ->orWhereHas('resident', function ($residentQuery) use ($query) {
                      $residentQuery->where('name', 'like', '%' . $query . '%');
                  })
                  ->orWhereHas('staff', function ($staffQuery) use ($query) {
                      $staffQuery->where('name', 'like', '%' . $query . '%');
                  });
            })
            ->with(['resident', 'staff', 'latestMessages' => function ($query) {
                $query->limit(1)->with('sender');
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Clean up old typing indicators.
     */
    public function cleanupTypingIndicators(): void
    {
        TypingIndicator::cleanupOldIndicators();
    }
}
