<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create a new transaction
     */
    public function create(array $data, User $resident): Transaction
    {
        return DB::transaction(function () use ($data, $resident) {
            $submittedDocuments = [];

            // Handle file uploads if present
            if (isset($data['submitted_documents']) && is_array($data['submitted_documents'])) {
                foreach ($data['submitted_documents'] as $documentType => $files) {
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                $path = $file->store('submitted-documents', 'public');
                                $submittedDocuments[] = [
                                    'document_type' => $documentType,
                                    'name' => $file->getClientOriginalName(),
                                    'path' => $path,
                                    'size' => $file->getSize(),
                                    'mime_type' => $file->getMimeType(),
                                ];
                            }
                        }
                    }
                }
            }

            // Get document type to populate document_type_id
            $documentType = null;
            if (isset($data['type'])) {
                $documentType = DocumentType::where('code', $data['type'])->first();
                if (!$documentType) {
                    throw new \Exception("Document type with code '{$data['type']}' not found. Please select a valid document type.");
                }
            } elseif (isset($data['document_type_id'])) {
                $documentType = DocumentType::find($data['document_type_id']);
                if (!$documentType) {
                    throw new \Exception("Document type with ID '{$data['document_type_id']}' not found.");
                }
            } else {
                throw new \Exception("Document type is required. Please select a valid document type.");
            }

            $transaction = Transaction::create([
                'resident_id' => $resident->id,
                'document_type_id' => $documentType->id,
                'type' => $data['type'] ?? $documentType->code,
                'title' => $data['title'],
                'required_documents' => $data['required_documents'] ?? [],
                'submitted_documents' => $submittedDocuments,
                'resident_input_data' => $data['required_fields'] ?? [],
                'fee_amount' => $data['fee_amount'] ?? $documentType->fee_amount ?? 0,
            ]);

            return $transaction;
        });
    }

    /**
     * Update transaction status and assign staff
     */
    public function updateStatus(Transaction $transaction, string $status, ?User $staff = null, array $data = []): Transaction
    {
        return DB::transaction(function () use ($transaction, $status, $staff, $data) {
            $updateData = ['status' => $status];

            if ($staff) {
                $updateData['staff_id'] = $staff->id;
            }

            if ($status === 'in_progress' && !$transaction->processed_at) {
                $updateData['processed_at'] = now();
            }

            if ($status === 'completed') {
                $updateData['completed_at'] = now();
            }

            if (isset($data['staff_notes'])) {
                $updateData['staff_notes'] = $data['staff_notes'];
            }

            // Handle officer_of_the_day - ALWAYS process it if present in data
            if (array_key_exists('officer_of_the_day', $data)) {
                $officerValue = $data['officer_of_the_day'];
                // Convert empty string to null, otherwise trim and save
                if (is_string($officerValue)) {
                    $trimmed = trim($officerValue);
                    $updateData['officer_of_the_day'] = $trimmed !== '' ? $trimmed : null;
                } else {
                    $updateData['officer_of_the_day'] = $officerValue ?: null;
                }
                
                \Log::info('TransactionService: Processing officer_of_the_day', [
                    'raw_value' => $officerValue,
                    'raw_type' => gettype($officerValue),
                    'processed_value' => $updateData['officer_of_the_day'],
                    'will_be_in_updateData' => isset($updateData['officer_of_the_day']),
                ]);
            } else {
                \Log::warning('TransactionService: officer_of_the_day NOT in data array', [
                    'available_keys' => array_keys($data),
                    'data_keys_count' => count($data),
                    'data_contents' => $data,
                ]);
            }

            if (isset($data['rejection_reason'])) {
                $updateData['rejection_reason'] = $data['rejection_reason'];
            }

            if (isset($data['document_content'])) {
                $updateData['generated_document_data'] = [
                    'content' => $data['document_content'],
                    'generated_at' => now()->toIso8601String(),
                    'generated_by' => $staff?->id,
                ];
            }

            \Log::info('TransactionService: Final updateData before save', [
                'updateData_keys' => array_keys($updateData),
                'officer_of_the_day_in_updateData' => isset($updateData['officer_of_the_day']),
                'officer_value' => $updateData['officer_of_the_day'] ?? 'NOT SET',
                'full_updateData' => $updateData,
            ]);

            // Perform the update
            $result = $transaction->update($updateData);
            
            \Log::info('TransactionService: After update', [
                'transaction_id' => $transaction->id,
                'update_result' => $result,
                'officer_of_the_day_before_refresh' => $transaction->officer_of_the_day,
                'officer_of_the_day_after_refresh' => $transaction->fresh()->officer_of_the_day,
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Get transactions for a resident
     */
    public function getResidentTransactions(User $resident, array $filters = []): Collection
    {
        $query = $resident->transactions()->with(['staff', 'documentType']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['document_type_id'])) {
            $query->where('document_type_id', $filters['document_type_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get transactions for staff management
     */
    public function getStaffTransactions(array $filters = [], int $perPage = 15)
    {
        $query = Transaction::with(['resident', 'staff', 'documentType']);

        if (isset($filters['status']) && $filters['status'] && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type']) && $filters['type'] && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['document_type_id']) && $filters['document_type_id'] && $filters['document_type_id'] !== 'all') {
            $query->where('document_type_id', $filters['document_type_id']);
        }

        if (isset($filters['payment_status']) && $filters['payment_status'] && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['staff_id']) && $filters['staff_id']) {
            $query->where('staff_id', $filters['staff_id']);
        }

        // Search functionality
        if (isset($filters['search']) && $filters['search']) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('title', 'like', "%{$searchTerm}%")
                  ->orWhereHas('resident', function ($residentQuery) use ($searchTerm) {
                      $residentQuery->where('first_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('documentType', function ($docTypeQuery) use ($searchTerm) {
                      $docTypeQuery->where('name', 'like', "%{$searchTerm}%")
                                   ->orWhere('code', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Sorting functionality
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        // Handle special sorting cases
        if ($sortField === 'resident') {
            $query->join('users as residents', 'transactions.resident_id', '=', 'residents.id')
                  ->orderBy('residents.first_name', $sortDirection)
                  ->select('transactions.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Transaction::count(),
            'pending' => Transaction::where('status', 'pending')->count(),
            'in_progress' => Transaction::where('status', 'in_progress')->count(),
            'completed' => Transaction::where('status', 'completed')->count(),
            'rejected' => Transaction::where('status', 'rejected')->count(),
        ];
    }

    /**
     * Get transaction types with their requirements from database
     */
    public function getTransactionTypes(): array
    {
        return \App\Models\DocumentType::active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($documentType) {
                return [
                    $documentType->code => [
                        'name' => $documentType->name,
                        'description' => $documentType->description,
                        'required_documents' => $documentType->required_documents ?? [],
                        'required_fields' => $documentType->required_fields ?? [],
                        'fee' => $documentType->fee_amount,
                        'processing_days' => $documentType->processing_days,
                        'requires_payment' => $documentType->requires_payment,
                        'requires_approval' => $documentType->requires_approval,
                        'category' => $documentType->category,
                    ],
                ];
            })
            ->toArray();
    }
}
