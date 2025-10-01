<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Conversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'resident_id',
        'staff_id',
        'subject',
        'last_message',
        'last_message_at',
        'is_active',
        'resident_has_unread',
        'staff_has_unread',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
        'is_active' => 'boolean',
        'resident_has_unread' => 'boolean',
        'staff_has_unread' => 'boolean',
    ];

    /**
     * Get the resident that owns the conversation.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    /**
     * Get the staff member that owns the conversation.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest messages for the conversation.
     */
    public function latestMessages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the typing indicators for the conversation.
     */
    public function typingIndicators(): HasMany
    {
        return $this->hasMany(TypingIndicator::class);
    }

    /**
     * Get unread messages count for a specific user.
     */
    public function getUnreadCountForUser(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark all messages as read for a specific user.
     */
    public function markAsReadForUser(User $user): void
    {
        $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);

        // Update conversation unread flags
        if ($user->role === 'resident') {
            $this->update(['resident_has_unread' => false]);
        } else {
            $this->update(['staff_has_unread' => false]);
        }
    }

    /**
     * Get the other participant in the conversation.
     */
    public function getOtherParticipant(User $user): User
    {
        if ($user->role === 'resident') {
            return $this->staff;
        } else {
            return $this->resident;
        }
    }

    /**
     * Check if user is a participant in this conversation.
     */
    public function isParticipant(User $user): bool
    {
        if ($user->role === 'resident') {
            return $user->id === $this->resident_id;
        } else {
            // For staff/admin users, any user with staff/admin role can participate
            return in_array($user->role, ['staff', 'admin']);
        }
    }

    /**
     * Get formatted last message time.
     */
    public function getFormattedLastMessageTimeAttribute(): string
    {
        if (!$this->last_message_at) {
            return 'No messages';
        }

        return $this->last_message_at->diffForHumans();
    }

    /**
     * Scope to get conversations for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->role === 'resident') {
            return $query->where('resident_id', $user->id);
        } else {
            // For staff/admin users, show all conversations (shared inbox approach)
            return $query->whereNotNull('resident_id');
        }
    }

    /**
     * Scope to get active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get conversations with unread messages for a user.
     */
    public function scopeWithUnreadForUser($query, User $user)
    {
        if ($user->id === $this->resident_id) {
            return $query->where('resident_has_unread', true);
        } else {
            return $query->where('staff_has_unread', true);
        }
    }
}
