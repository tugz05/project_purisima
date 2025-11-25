<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalamityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'staff_id',
        'latitude',
        'longitude',
        'address',
        'location_notes',
        'calamity_type',
        'severity',
        'description',
        'needs',
        'specific_needs',
        'number_of_people',
        'has_elderly',
        'has_children',
        'has_pwd',
        'has_pregnant',
        'medical_conditions',
        'status',
        'staff_notes',
        'assistance_provided',
        'acknowledged_at',
        'assisted_at',
        'resolved_at',
        'location_shared',
        'location_updated_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'needs' => 'array',
        'has_elderly' => 'boolean',
        'has_children' => 'boolean',
        'has_pwd' => 'boolean',
        'has_pregnant' => 'boolean',
        'location_shared' => 'boolean',
        'acknowledged_at' => 'datetime',
        'assisted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'location_updated_at' => 'datetime',
    ];

    /**
     * Get the resident that made this report.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    /**
     * Get the staff member assigned to this report.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
