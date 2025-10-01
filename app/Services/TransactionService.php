<?php

namespace App\Services;

use App\Models\Transaction;
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

            $transaction = Transaction::create([
                'resident_id' => $resident->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'required_documents' => $data['required_documents'] ?? [],
                'submitted_documents' => $submittedDocuments,
                'fee_amount' => $data['fee_amount'] ?? 0,
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

            if (isset($data['rejection_reason'])) {
                $updateData['rejection_reason'] = $data['rejection_reason'];
            }

            $transaction->update($updateData);

            return $transaction->fresh();
        });
    }

    /**
     * Get transactions for a resident
     */
    public function getResidentTransactions(User $resident, array $filters = []): Collection
    {
        $query = $resident->transactions()->with('staff');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get transactions for staff management
     */
    public function getStaffTransactions(array $filters = [], int $perPage = 15)
    {
        $query = Transaction::with(['resident', 'staff']);

        if (isset($filters['status']) && $filters['status'] && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type']) && $filters['type'] && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
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
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('resident', function ($residentQuery) use ($searchTerm) {
                      $residentQuery->where('first_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('name', 'like', "%{$searchTerm}%");
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
