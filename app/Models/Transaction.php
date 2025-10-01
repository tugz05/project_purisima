<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'resident_id',
        'staff_id',
        'type',
        'title',
        'description',
        'status',
        'staff_notes',
        'rejection_reason',
        'required_documents',
        'submitted_documents',
        'fee_amount',
        'fee_paid',
        'payment_status',
        'payment_method',
        'amount_paid',
        'payment_reference',
        'payment_notes',
        'payment_date',
        'payment_verified_at',
        'payment_verified_by',
        'receipt_number',
        'payment_proof',
        'submitted_at',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'submitted_documents' => 'array',
        'fee_amount' => 'decimal:2',
        'fee_paid' => 'boolean',
        'amount_paid' => 'decimal:2',
        'payment_proof' => 'array',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_date' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type', 'code');
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Check if payment is required for this transaction
     */
    public function requiresPayment(): bool
    {
        return $this->fee_amount > 0;
    }

    /**
     * Check if payment has been completed
     */
    public function isPaymentCompleted(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Get payment status badge color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'refunded' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get formatted payment amount
     */
    public function getFormattedPaymentAmountAttribute(): string
    {
        return '₱' . number_format((float) $this->amount_paid, 2);
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'paymaya' => 'PayMaya',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            default => 'N/A',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN-' . strtoupper(uniqid());
            }
        });
    }
}
