<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'document_type_id',
        'name',
        'description',
        'template_content',
        'available_tags',
        'required_fields',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'available_tags' => 'array',
        'required_fields' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the document type that owns this template
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Extract tags from template content
     */
    public function extractTags(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->template_content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Get default template for a document type
     */
    public static function getDefaultForDocumentType(int $documentTypeId): ?self
    {
        return static::where('document_type_id', $documentTypeId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
