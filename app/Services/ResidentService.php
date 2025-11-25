<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResidentService
{
    /**
     * Get all residents with pagination and filters
     */
    public function getResidents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::where('role', 'resident')
            ->withCount('transactions')
            ->select(['id', 'name', 'email', 'first_name', 'middle_name', 'last_name', 'phone', 'purok', 'barangay', 'municipality', 'province', 'birth_date', 'sex', 'civil_status', 'occupation', 'photo_url', 'profile_completed_at', 'created_at']);

        // Search functionality
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by purok
        if (isset($filters['purok']) && $filters['purok'] && $filters['purok'] !== 'all') {
            $query->where('purok', $filters['purok']);
        }

        // Filter by profile completion
        if (isset($filters['profile_completed']) && $filters['profile_completed'] !== 'all') {
            if ($filters['profile_completed'] === 'completed') {
                $query->whereNotNull('profile_completed_at');
            } elseif ($filters['profile_completed'] === 'incomplete') {
                $query->whereNull('profile_completed_at');
            }
        }

        // Sort
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get resident statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => User::where('role', 'resident')->count(),
            'profile_completed' => User::where('role', 'resident')
                ->whereNotNull('profile_completed_at')
                ->count(),
            'profile_incomplete' => User::where('role', 'resident')
                ->whereNull('profile_completed_at')
                ->count(),
            'with_transactions' => User::where('role', 'resident')
                ->has('transactions')
                ->count(),
        ];
    }

    /**
     * Get all unique puroks from residents
     */
    public function getPuroks(): array
    {
        return User::where('role', 'resident')
            ->whereNotNull('purok')
            ->distinct()
            ->orderBy('purok')
            ->pluck('purok')
            ->toArray();
    }

    /**
     * Get a single resident by ID
     */
    public function getResident(int $id): ?User
    {
        return User::where('role', 'resident')
            ->with(['transactions', 'householdProfile'])
            ->find($id);
    }
}

