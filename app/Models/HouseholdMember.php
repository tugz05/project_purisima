<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class HouseholdMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'relationship',
        'birth_date',
        'sex',
        'civil_status',
        'occupation',
        'educational_attainment',
        'is_working',
        'is_student',
        'is_senior_citizen',
        'is_pwd',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_working' => 'boolean',
        'is_student' => 'boolean',
        'is_senior_citizen' => 'boolean',
        'is_pwd' => 'boolean',
    ];

    /**
     * Get the user that owns the household member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full name of the household member.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;

        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }

        $name .= ' ' . $this->last_name;

        if ($this->suffix) {
            $name .= ' ' . $this->suffix;
        }

        return $name;
    }

    /**
     * Get the age of the household member.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Get the formatted birth date.
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date instanceof \Carbon\Carbon 
            ? $this->birth_date->format('M d, Y') 
            : Carbon::parse($this->birth_date)->format('M d, Y');
    }

    /**
     * Get the relationship display name.
     */
    public function getRelationshipDisplayAttribute(): string
    {
        return match($this->relationship) {
            'self' => 'Self (Household Head)',
            'spouse' => 'Spouse',
            'child' => 'Child',
            'parent' => 'Parent',
            'sibling' => 'Sibling',
            'grandparent' => 'Grandparent',
            'grandchild' => 'Grandchild',
            'other' => 'Other',
            default => ucfirst($this->relationship),
        };
    }
}
