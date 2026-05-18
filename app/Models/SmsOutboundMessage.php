<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsOutboundMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'to',
        'message',
        'status',
        'provider_message_id',
        'error_message',
        'context_type',
        'context_id',
        'attempt_number',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'attempt_number' => 'integer',
    ];

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
