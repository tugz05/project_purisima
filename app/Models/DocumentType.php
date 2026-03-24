<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    protected $appends = [
        'input_fields',
    ];

    /**
     * @return array<int, array{key: string, label: string, type: string, required: bool, placeholder: ?string, options: array<int, string>}>
     */
    public static function normalizeDynamicInputFields(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $out = [];
        foreach ($raw as $index => $item) {
            if (is_string($item)) {
                $label = trim($item);
                if ($label === '') {
                    continue;
                }
                $key = self::uniqueFieldKey(self::slugFieldKey($label, $index), $out);
                $out[] = [
                    'key' => $key,
                    'label' => $label,
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => null,
                    'options' => [],
                ];

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $label = isset($item['label']) ? trim((string) $item['label']) : '';
            $keySource = (isset($item['key']) && is_string($item['key']) && trim($item['key']) !== '')
                ? trim($item['key'])
                : ($label !== '' ? $label : 'field');
            $key = self::uniqueFieldKey(self::sanitizeFieldKey($keySource, $index), $out);

            $type = strtolower((string) ($item['type'] ?? 'text'));
            $allowed = ['text', 'textarea', 'number', 'date', 'email', 'select'];
            if (! in_array($type, $allowed, true)) {
                $type = 'text';
            }

            $options = [];
            if ($type === 'select' && isset($item['options'])) {
                $opts = $item['options'];
                if (is_string($opts)) {
                    $opts = array_map('trim', explode(',', $opts));
                }
                if (is_array($opts)) {
                    foreach ($opts as $o) {
                        if (is_string($o) && trim($o) !== '') {
                            $options[] = trim($o);
                        }
                    }
                }
            }

            $out[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : $key,
                'type' => $type,
                'required' => filter_var($item['required'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'placeholder' => isset($item['placeholder']) && is_string($item['placeholder']) && trim($item['placeholder']) !== ''
                    ? trim($item['placeholder'])
                    : null,
                'options' => $options,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, array{key: string, label: string, type: string, required: bool, placeholder: ?string, options: array<int, string>}>
     */
    public static function coerceRequiredFieldsForStorage(array $raw): array
    {
        return self::normalizeDynamicInputFields($raw);
    }

    protected function inputFields(): Attribute
    {
        return Attribute::make(
            get: fn (): array => self::normalizeDynamicInputFields($this->required_fields),
        );
    }

    /**
     * @param  array<int, array{key: string}>  $existing
     */
    private static function uniqueFieldKey(string $base, array $existing): string
    {
        $used = array_column($existing, 'key');
        if (! in_array($base, $used, true)) {
            return $base;
        }
        $n = 2;
        while (in_array($base.'_'.$n, $used, true)) {
            $n++;
        }

        return $base.'_'.$n;
    }

    private static function slugFieldKey(string $label, int $index): string
    {
        $slug = Str::slug($label, '_');
        if ($slug === '') {
            return 'field_'.$index;
        }

        return self::sanitizeFieldKey($slug, $index);
    }

    private static function sanitizeFieldKey(string $key, int $index): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '');
        $key = trim($key, '_');
        if ($key === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            return 'field_'.$index;
        }

        return $key;
    }

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
        return '₱'.number_format((float) $this->fee_amount, 2);
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
            $code = $baseCode.'-'.$counter;
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
