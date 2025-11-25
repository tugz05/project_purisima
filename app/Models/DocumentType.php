<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'fee_amount',
        'required_documents',
        'required_fields',
        'processing_steps',
        'processing_days',
        'is_active',
        'requires_payment',
        'requires_approval',
        'category',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'required_fields' => 'array',
        'processing_steps' => 'array',
        'fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_payment' => 'boolean',
        'requires_approval' => 'boolean',
        'processing_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get transactions of this document type
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'type', 'code');
    }

    /**
     * Get certificate templates for this document type
     */
    public function certificateTemplates(): HasMany
    {
        return $this->hasMany(CertificateTemplate::class);
    }

    /**
     * Get default certificate template for this document type
     */
    public function defaultCertificateTemplate()
    {
        return $this->hasOne(CertificateTemplate::class)->where('is_default', true)->where('is_active', true);
    }

    /**
     * Scope to get only active document types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order then name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get formatted fee amount
     */
    public function getFormattedFeeAttribute(): string
    {
        return '₱' . number_format((float) $this->fee_amount, 2);
    }

    /**
     * Check if this document type is free
     */
    public function getIsFreeAttribute(): bool
    {
        return $this->fee_amount == 0;
    }

    /**
     * Generate a unique code from the name
     */
    public static function generateCode(string $name, ?int $excludeId = null): string
    {
        // Convert name to code format: lowercase, replace spaces/special chars with hyphens
        $baseCode = strtolower(trim($name));
        $baseCode = preg_replace('/[^a-z0-9\s-]/', '', $baseCode);
        $baseCode = preg_replace('/\s+/', '-', $baseCode);
        $baseCode = trim($baseCode, '-');

        // Ensure it's not empty
        if (empty($baseCode)) {
            $baseCode = 'document-type';
        }

        $code = $baseCode;
        $counter = 1;

        // Check for uniqueness
        while (static::where('code', $code)->when($excludeId, function ($query) use ($excludeId) {
            return $query->where('id', '!=', $excludeId);
        })->exists()) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Boot method to auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($documentType) {
            if (empty($documentType->code)) {
                $documentType->code = static::generateCode($documentType->name);
            }
        });

        static::updating(function ($documentType) {
            // Only regenerate code if name changed and code is empty
            if ($documentType->isDirty('name') && empty($documentType->code)) {
                $documentType->code = static::generateCode($documentType->name, $documentType->id);
            }
        });
    }
}
