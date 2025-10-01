<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public Conversation $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];

        // Also notify the other participant via their user channel for global badges
        $recipientIds = [];
        if ($this->message->sender_id !== $this->conversation->resident_id) {
            $recipientIds[] = $this->conversation->resident_id;
        }
        if ($this->message->sender_id !== $this->conversation->staff_id) {
            $recipientIds[] = $this->conversation->staff_id;
        }

        foreach ($recipientIds as $recipientId) {
            if ($recipientId) {
                $channels[] = new PrivateChannel('App.Models.User.' . $recipientId);
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'attachments' => $this->message->attachments,
                'is_read' => $this->message->is_read,
                'is_edited' => $this->message->is_edited,
                'created_at' => $this->message->created_at->toISOString(),
                'display_time' => $this->message->display_time,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'email' => $this->message->sender->email,
                ],
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'last_message' => $this->conversation->last_message,
                'last_message_at' => $this->conversation->last_message_at?->toISOString(),
                'resident_has_unread' => $this->conversation->resident_has_unread,
                'staff_has_unread' => $this->conversation->staff_has_unread,
            ],
        ];
    }
}
