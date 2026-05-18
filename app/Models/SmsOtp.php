<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsOtp extends Model
{
    protected $fillable = [
        'phone',
        'code_hash',
        'purpose',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected $hidden = ['code_hash'];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
