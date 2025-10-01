<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_profile_id',
        'first_name',
        'middle_name',
        'last_name',
        'relationship_to_head',
        'birth_date',
        'sex',
        'civil_status',
        'educational_attainment',
        'occupation',
        'employment_status',
        'monthly_income',
        'is_working',
        'is_student',
        'is_senior_citizen',
        'is_pwd',
        'disability_details',
        'additional_notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'monthly_income' => 'decimal:2',
        'is_working' => 'boolean',
        'is_student' => 'boolean',
        'is_senior_citizen' => 'boolean',
        'is_pwd' => 'boolean',
    ];

    /**
     * Get the household profile that owns the family member.
     */
    public function householdProfile(): BelongsTo
    {
        return $this->belongsTo(HouseholdProfile::class);
    }

    /**
     * Get the full name of the family member.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;

        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }

        $name .= ' ' . $this->last_name;

        return $name;
    }

    /**
     * Get the age of the family member.
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

        return $this->birth_date instanceof \Carbon\Carbon ? $this->birth_date->format('M d, Y') : null;
    }

    /**
     * Check if the member is a minor (under 18).
     */
    public function getIsMinorAttribute(): bool
    {
        return $this->age !== null && $this->age < 18;
    }

    /**
     * Check if the member is an adult (18 and above).
     */
    public function getIsAdultAttribute(): bool
    {
        return $this->age !== null && $this->age >= 18;
    }

    /**
     * Get the relationship display name.
     */
    public function getRelationshipDisplayAttribute(): string
    {
        return match($this->relationship_to_head) {
            'self' => 'Household Head',
            'spouse' => 'Spouse',
            'child' => 'Child',
            'parent' => 'Parent',
            'sibling' => 'Sibling',
            'grandparent' => 'Grandparent',
            'grandchild' => 'Grandchild',
            'other' => 'Other',
            default => ucfirst($this->relationship_to_head),
        };
    }

    /**
     * Get the employment status display name.
     */
    public function getEmploymentStatusDisplayAttribute(): string
    {
        return match($this->employment_status) {
            'employed' => 'Employed',
            'unemployed' => 'Unemployed',
            'student' => 'Student',
            'retired' => 'Retired',
            'housewife' => 'Housewife',
            'other' => 'Other',
            default => ucfirst($this->employment_status ?? 'Not specified'),
        };
    }

    /**
     * Get the educational attainment display name.
     */
    public function getEducationalAttainmentDisplayAttribute(): string
    {
        return match($this->educational_attainment) {
            'none' => 'No formal education',
            'elementary' => 'Elementary',
            'high_school' => 'High School',
            'college' => 'College',
            'graduate' => 'Graduate School',
            'other' => 'Other',
            default => ucfirst($this->educational_attainment ?? 'Not specified'),
        };
    }
}
