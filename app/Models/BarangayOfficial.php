<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarangayOfficial extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'position',
        'email',
        'phone',
        'address',
        'birth_date',
        'gender',
        'civil_status',
        'photo',
        'biography',
        'term_start',
        'term_end',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'term_start' => 'integer',
        'term_end' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Accessor for full name
    public function getFullNameAttribute()
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

    // Scope for active officials
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for ordering by position
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('position');
    }
}
