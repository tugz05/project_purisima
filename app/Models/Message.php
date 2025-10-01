<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'attachments',
        'is_read',
        'read_at',
        'is_edited',
        'edited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Mark the message as edited.
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => Carbon::now(),
        ]);
    }

    /**
     * Get formatted time attribute.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->format('M d, Y g:i A');
    }

    /**
     * Get short time attribute.
     */
    public function getShortTimeAttribute(): string
    {
        return $this->created_at->format('g:i A');
    }

    /**
     * Check if message was sent today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->created_at->isToday();
    }

    /**
     * Check if message was sent yesterday.
     */
    public function getIsYesterdayAttribute(): bool
    {
        return $this->created_at->isYesterday();
    }

    /**
     * Get display time for message.
     */
    public function getDisplayTimeAttribute(): string
    {
        if ($this->is_today) {
            return $this->short_time;
        } elseif ($this->is_yesterday) {
            return 'Yesterday ' . $this->short_time;
        } else {
            return $this->formatted_time;
        }
    }

    /**
     * Check if message has attachments.
     */
    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments) && is_array($this->attachments);
    }

    /**
     * Get attachment count.
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->has_attachments ? count($this->attachments) : 0;
    }

    /**
     * Scope to get unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get messages by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get messages with attachments.
     */
    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachments');
    }
}