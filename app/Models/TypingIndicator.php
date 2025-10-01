<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TypingIndicator extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'started_typing_at',
        'last_activity_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_typing_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the typing indicator.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that owns the typing indicator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if typing indicator is still active (within last 3 seconds).
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->last_activity_at->diffInSeconds(Carbon::now()) <= 3;
    }

    /**
     * Update the last activity timestamp.
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => Carbon::now()]);
    }

    /**
     * Start typing indicator.
     */
    public static function startTyping(int $conversationId, int $userId): self
    {
        return self::updateOrCreate(
            [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ],
            [
                'started_typing_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Stop typing indicator.
     */
    public function stopTyping(): void
    {
        $this->delete();
    }

    /**
     * Clean up old typing indicators (older than 5 seconds).
     */
    public static function cleanupOldIndicators(): void
    {
        self::where('last_activity_at', '<', Carbon::now()->subSeconds(5))->delete();
    }

    /**
     * Get active typing indicators for a conversation.
     */
    public static function getActiveForConversation(int $conversationId, int $excludeUserId = null)
    {
        $query = self::where('conversation_id', $conversationId)
            ->where('last_activity_at', '>', Carbon::now()->subSeconds(3));

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->with('user')->get();
    }
}
