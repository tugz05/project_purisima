<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'role',
        'provider', 'provider_id', 'photo_url',
        'first_name', 'middle_name', 'last_name', 'phone', 'birth_date', 'sex', 'civil_status', 'occupation',
        'purok', 'barangay', 'municipality', 'province', 'country',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'resident_id');
    }

    public function assignedTransactions()
    {
        return $this->hasMany(Transaction::class, 'staff_id');
    }

    /**
     * Get the household profile for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function householdProfile()
    {
        return $this->hasOne(HouseholdProfile::class);
    }

    /**
     * Get the notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the conversations where the user is a resident.
     */
    public function residentConversations()
    {
        return $this->hasMany(Conversation::class, 'resident_id');
    }

    /**
     * Get the conversations where the user is a staff member.
     */
    public function staffConversations()
    {
        return $this->hasMany(Conversation::class, 'staff_id');
    }

    /**
     * Get all conversations for the user.
     */
    public function conversations()
    {
        return Conversation::where('resident_id', $this->id)
            ->orWhere('staff_id', $this->id);
    }

    /**
     * Get messages sent by the user.
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get typing indicators for the user.
     */
    public function typingIndicators()
    {
        return $this->hasMany(TypingIndicator::class);
    }
}
