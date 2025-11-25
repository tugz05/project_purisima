<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;

class DocumentTypeService
{
    /**
     * Create a new document type
     */
    public function create(array $data): DocumentType
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate code if not provided
            if (empty($data['code']) && !empty($data['name'])) {
                $data['code'] = DocumentType::generateCode($data['name']);
            }

            // Ensure code is unique
            if (isset($data['code'])) {
                $baseCode = $data['code'];
                $code = $baseCode;
                $counter = 1;
                
                while (DocumentType::where('code', $code)->exists()) {
                    $code = $baseCode . '-' . $counter;
                    $counter++;
                }
                $data['code'] = $code;
            }

            return DocumentType::create($data);
        });
    }

    /**
     * Update a document type
     */
    public function update(DocumentType $documentType, array $data): DocumentType
    {
        return DB::transaction(function () use ($documentType, $data) {
            // Auto-generate code if name changed and code is empty
            if (isset($data['name']) && $documentType->name !== $data['name'] && empty($data['code'])) {
                $data['code'] = DocumentType::generateCode($data['name'], $documentType->id);
            }

            // Ensure code is unique if changed
            if (isset($data['code']) && $documentType->code !== $data['code']) {
                $baseCode = $data['code'];
                $code = $baseCode;
                $counter = 1;
                
                while (DocumentType::where('code', $code)->where('id', '!=', $documentType->id)->exists()) {
                    $code = $baseCode . '-' . $counter;
                    $counter++;
                }
                $data['code'] = $code;
            }

            $documentType->update($data);

            return $documentType->fresh();
        });
    }

    /**
     * Delete a document type
     */
    public function delete(DocumentType $documentType): bool
    {
        return DB::transaction(function () use ($documentType) {
            // Check if document type has associated transactions
            if ($documentType->transactions()->count() > 0) {
                throw new \Exception('Cannot delete document type with associated transactions.');
            }

            return $documentType->delete();
        });
    }

    /**
     * Get all document types with optional filters
     */
    public function getAll(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = DocumentType::query();

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->ordered()->get();
    }
}

