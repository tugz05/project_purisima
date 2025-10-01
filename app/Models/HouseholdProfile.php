<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseholdProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'household_head_name',
        'household_head_relationship',
        'monthly_income',
        'income_source',
        'income_source_details',
        'total_family_members',
        'working_members',
        'dependent_members',
        'housing_type',
        'housing_details',
        'has_vehicle',
        'vehicle_details',
        'has_livestock',
        'livestock_details',
        'additional_notes',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'has_vehicle' => 'boolean',
        'has_livestock' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the household profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the family members for the household profile.
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Get the total monthly income including all family members.
     */
    public function getTotalMonthlyIncomeAttribute(): float
    {
        $familyIncome = $this->familyMembers()
            ->where('is_working', true)
            ->sum('monthly_income') ?? 0;

        return $this->monthly_income + $familyIncome;
    }

    /**
     * Get the household head member.
     */
    public function householdHead(): ?FamilyMember
    {
        return $this->familyMembers()
            ->where('relationship_to_head', 'self')
            ->first();
    }

    /**
     * Get working family members.
     */
    public function workingMembers(): HasMany
    {
        return $this->familyMembers()->where('is_working', true);
    }

    /**
     * Get student family members.
     */
    public function studentMembers(): HasMany
    {
        return $this->familyMembers()->where('is_student', true);
    }

    /**
     * Get senior citizen family members.
     */
    public function seniorCitizenMembers(): HasMany
    {
        return $this->familyMembers()->where('is_senior_citizen', true);
    }

    /**
     * Get PWD family members.
     */
    public function pwdMembers(): HasMany
    {
        return $this->familyMembers()->where('is_pwd', true);
    }
}
