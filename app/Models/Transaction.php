<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'resident_id',
        'staff_id',
        'document_type_id',
        'type',
        'title',
        'status',
        'staff_notes',
        'officer_of_the_day',
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
        'use_ai_generation',
        'ai_prompt_template',
        'ai_generated_content',
        'ai_generated_at',
        'generated_document_data',
        'generated_document_path',
        'document_generated_at',
        'resident_input_data',
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
        'use_ai_generation' => 'boolean',
        'ai_generated_content' => 'array',
        'ai_generated_at' => 'datetime',
        'generated_document_data' => 'array',
        'document_generated_at' => 'datetime',
        'resident_input_data' => 'array',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Get the document type relationship
     * Primary: Uses document_type_id (foreign key)
     * Fallback: Uses type code if document_type_id is not set
     */
    public function documentType(): BelongsTo
    {
        // Primary relationship using document_type_id
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * Get document type by code (fallback for backward compatibility)
     */
    public function getDocumentTypeByCodeAttribute()
    {
        if ($this->document_type_id) {
            return $this->documentType;
        }

        if ($this->type) {
            return DocumentType::where('code', $this->type)->first();
        }

        return null;
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
        return '₱'.number_format((float) $this->amount_paid, 2);
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

    /**
     * Flat resident / requestor data for certificates, templates, and AI (account or walk-in manual).
     *
     * @return array<string, mixed>
     */
    public function resolveCertificateResidentData(): array
    {
        $input = is_array($this->resident_input_data) ? $this->resident_input_data : [];
        $manual = is_array($input['__manual_requestor'] ?? null) ? $input['__manual_requestor'] : [];

        $resident = $this->relationLoaded('resident') ? $this->resident : $this->resident()->first();

        if ($resident !== null) {
            $nameParts = preg_split('/\s+/', trim((string) ($resident->name ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? $nameParts[array_key_last($nameParts)] : '';

            $data = [
                'name' => $resident->name ?? '',
                'first_name' => $resident->first_name ?? $firstName,
                'middle_name' => $resident->middle_name ?? '',
                'last_name' => $resident->last_name ?? $lastName,
                'email' => $resident->email ?? '',
                'phone' => $resident->phone ?? '',
                'address' => $resident->address ?? '',
                'purok' => $resident->purok ?? '',
                'barangay' => $resident->barangay ?? 'Barangay Purisima',
                'municipality' => $resident->municipality ?? 'Tago',
                'province' => $resident->province ?? 'Surigao del Sur',
            ];
        } else {
            $fullName = trim((string) ($manual['full_name'] ?? ''));
            $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? $nameParts[array_key_last($nameParts)] : '';

            $data = [
                'name' => $fullName,
                'first_name' => $firstName,
                'middle_name' => '',
                'last_name' => $lastName,
                'email' => (string) ($manual['email'] ?? ''),
                'phone' => (string) ($manual['phone'] ?? ''),
                'address' => (string) ($manual['address'] ?? ''),
                'purok' => (string) ($manual['purok'] ?? ''),
                'barangay' => 'Barangay Purisima',
                'municipality' => 'Tago',
                'province' => 'Surigao del Sur',
            ];
        }

        $data['date'] = now()->format('F d, Y');
        $data['date_issued'] = now()->format('F d, Y');
        $data['time'] = now()->format('h:i A');
        $data['year'] = now()->year;
        $data['month'] = now()->format('F');
        $data['day'] = now()->day;
        $data['punong_barangay'] = 'EMMANUEL P. ISIANG';
        $data['officer_of_day'] = 'Officer of the Day';

        $extras = [];
        foreach (Arr::except($input, ['__manual_requestor']) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $extras[$key] = $value;
            }
        }

        return array_merge($data, $extras);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN-'.strtoupper(uniqid());
            }

            // Auto-populate document_type_id from type code if not set
            if (empty($transaction->document_type_id) && ! empty($transaction->type)) {
                $documentType = DocumentType::where('code', $transaction->type)->first();
                if ($documentType) {
                    $transaction->document_type_id = $documentType->id;
                }
            }
        });

        static::updating(function ($transaction) {
            // Auto-populate document_type_id from type code if not set
            if (empty($transaction->document_type_id) && ! empty($transaction->type)) {
                $documentType = DocumentType::where('code', $transaction->type)->first();
                if ($documentType) {
                    $transaction->document_type_id = $documentType->id;
                }
            }
        });
    }
}
