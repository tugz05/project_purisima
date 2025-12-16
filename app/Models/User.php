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
        'first_name', 'middle_name', 'last_name', 'suffix', 'phone', 'birth_date', 'sex', 'civil_status', 'occupation',
        'purok', 'barangay', 'municipality', 'province', 'country',
        'profile_completed_at',
        'latitude', 'longitude', 'location_shared', 'location_updated_at',
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
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'location_shared' => 'boolean',
            'location_updated_at' => 'datetime',
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
     * Get the household members for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function householdMembers()
    {
        return $this->hasMany(HouseholdMember::class);
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

    /**
     * Get the user's full name attribute.
     * Automatically generates name from first_name, middle_name, last_name, and suffix.
     */
    public function getNameAttribute($value)
    {
        // Build name from individual components if they exist
        $parts = array_filter([
            $this->attributes['first_name'] ?? null,
            $this->attributes['middle_name'] ?? null,
            $this->attributes['last_name'] ?? null,
            $this->attributes['suffix'] ?? null,
        ]);

        // If we have individual name parts, use them; otherwise use the stored name value
        if (!empty($parts)) {
            return implode(' ', $parts);
        }

        // Fallback to stored name value or empty string
        return $value ?? '';
    }

    /**
     * Set the name attribute and automatically populate individual name fields if not set.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        // If individual name fields are not set, try to parse the name
        if (empty($this->attributes['first_name']) && !empty($value)) {
            $nameParts = explode(' ', trim($value), 4);
            
            if (count($nameParts) >= 1) {
                $this->attributes['first_name'] = $nameParts[0];
            }
            if (count($nameParts) >= 2) {
                $this->attributes['middle_name'] = $nameParts[1];
            }
            if (count($nameParts) >= 3) {
                // Check if last part is a suffix (Jr., Sr., II, III, etc.)
                $lastPart = $nameParts[count($nameParts) - 1];
                $suffixPattern = '/^(Jr\.?|Sr\.?|II|III|IV|V|VI|VII|VIII|IX|X)$/i';
                
                if (preg_match($suffixPattern, $lastPart)) {
                    $this->attributes['suffix'] = $lastPart;
                    $this->attributes['last_name'] = count($nameParts) >= 3 ? $nameParts[count($nameParts) - 2] : null;
                } else {
                    $this->attributes['last_name'] = $lastPart;
                }
            }
        }
    }

    /**
     * Boot method to automatically update name when individual fields change.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // If individual name fields are set, update the name attribute
            if (!empty($user->first_name) || !empty($user->last_name)) {
                $parts = array_filter([
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name,
                    $user->suffix,
                ]);

                if (!empty($parts)) {
                    $user->name = implode(' ', $parts);
                }
            }
        });
    }
}
