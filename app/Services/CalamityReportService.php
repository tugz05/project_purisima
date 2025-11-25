<?php

namespace App\Services;

use App\Models\CalamityReport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CalamityReportService
{
    /**
     * Create a new calamity report
     */
    public function create(array $data, User $resident): CalamityReport
    {
        return DB::transaction(function () use ($data, $resident) {
            return CalamityReport::create([
                'resident_id' => $resident->id,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'address' => $data['address'] ?? null,
                'location_notes' => $data['location_notes'] ?? null,
                'calamity_type' => $data['calamity_type'] ?? 'other',
                'severity' => $data['severity'] ?? 'medium',
                'description' => $data['description'] ?? null,
                'needs' => $data['needs'] ?? [],
                'specific_needs' => $data['specific_needs'] ?? null,
                'number_of_people' => $data['number_of_people'] ?? 1,
                'has_elderly' => $data['has_elderly'] ?? false,
                'has_children' => $data['has_children'] ?? false,
                'has_pwd' => $data['has_pwd'] ?? false,
                'has_pregnant' => $data['has_pregnant'] ?? false,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'location_shared' => $data['location_shared'] ?? true,
                'location_updated_at' => now(),
            ]);
        });
    }

    /**
     * Update a calamity report
     */
    public function update(CalamityReport $report, array $data, ?User $staff = null): CalamityReport
    {
        return DB::transaction(function () use ($report, $data, $staff) {
            $updateData = [];

            // Location updates
            if (isset($data['latitude'])) {
                $updateData['latitude'] = $data['latitude'];
            }
            if (isset($data['longitude'])) {
                $updateData['longitude'] = $data['longitude'];
            }
            if (isset($data['address'])) {
                $updateData['address'] = $data['address'];
            }
            if (isset($data['location_notes'])) {
                $updateData['location_notes'] = $data['location_notes'];
            }
            if (isset($data['latitude']) || isset($data['longitude'])) {
                $updateData['location_updated_at'] = now();
            }

            // Calamity information
            if (isset($data['calamity_type'])) {
                $updateData['calamity_type'] = $data['calamity_type'];
            }
            if (isset($data['severity'])) {
                $updateData['severity'] = $data['severity'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            // Needs
            if (isset($data['needs'])) {
                $updateData['needs'] = $data['needs'];
            }
            if (isset($data['specific_needs'])) {
                $updateData['specific_needs'] = $data['specific_needs'];
            }
            if (isset($data['number_of_people'])) {
                $updateData['number_of_people'] = $data['number_of_people'];
            }
            if (isset($data['has_elderly'])) {
                $updateData['has_elderly'] = $data['has_elderly'];
            }
            if (isset($data['has_children'])) {
                $updateData['has_children'] = $data['has_children'];
            }
            if (isset($data['has_pwd'])) {
                $updateData['has_pwd'] = $data['has_pwd'];
            }
            if (isset($data['has_pregnant'])) {
                $updateData['has_pregnant'] = $data['has_pregnant'];
            }
            if (isset($data['medical_conditions'])) {
                $updateData['medical_conditions'] = $data['medical_conditions'];
            }

            // Staff updates
            if ($staff) {
                $updateData['staff_id'] = $staff->id;
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
                
                // Set timestamps based on status
                if ($data['status'] === 'acknowledged' && !$report->acknowledged_at) {
                    $updateData['acknowledged_at'] = now();
                }
                if ($data['status'] === 'assisted' && !$report->assisted_at) {
                    $updateData['assisted_at'] = now();
                }
                if ($data['status'] === 'resolved' && !$report->resolved_at) {
                    $updateData['resolved_at'] = now();
                }
            }
            if (isset($data['staff_notes'])) {
                $updateData['staff_notes'] = $data['staff_notes'];
            }
            if (isset($data['assistance_provided'])) {
                $updateData['assistance_provided'] = $data['assistance_provided'];
            }

            $report->update($updateData);

            return $report->fresh();
        });
    }

    /**
     * Get all reports for staff with filters
     */
    public function getStaffReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CalamityReport::with(['resident', 'staff']);

        if (isset($filters['status']) && $filters['status'] && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['calamity_type']) && $filters['calamity_type'] && $filters['calamity_type'] !== 'all') {
            $query->where('calamity_type', $filters['calamity_type']);
        }

        if (isset($filters['severity']) && $filters['severity'] && $filters['severity'] !== 'all') {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('resident', function ($residentQuery) use ($search) {
                        $residentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get reports for a specific resident
     */
    public function getResidentReports(User $resident, array $filters = []): LengthAwarePaginator
    {
        $query = CalamityReport::where('resident_id', $resident->id)
            ->with(['staff']);

        if (isset($filters['status']) && $filters['status'] && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get all active reports with location for map display
     */
    public function getActiveReportsForMap(): array
    {
        return CalamityReport::where('location_shared', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('status', ['pending', 'acknowledged', 'in_progress'])
            ->with(['resident'])
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'resident_name' => $report->resident->name ?? 'Unknown',
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'address' => $report->address,
                    'calamity_type' => $report->calamity_type,
                    'severity' => $report->severity,
                    'status' => $report->status,
                    'needs' => $report->needs ?? [],
                    'number_of_people' => $report->number_of_people,
                    'description' => $report->description,
                ];
            })
            ->toArray();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        return [
            'total' => CalamityReport::count(),
            'pending' => CalamityReport::where('status', 'pending')->count(),
            'acknowledged' => CalamityReport::where('status', 'acknowledged')->count(),
            'in_progress' => CalamityReport::where('status', 'in_progress')->count(),
            'assisted' => CalamityReport::where('status', 'assisted')->count(),
            'resolved' => CalamityReport::where('status', 'resolved')->count(),
            'critical' => CalamityReport::where('severity', 'critical')->count(),
            'high' => CalamityReport::where('severity', 'high')->count(),
        ];
    }
}

