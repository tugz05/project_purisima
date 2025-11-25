<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CalamityReport;
use App\Services\CalamityReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalamityReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CalamityReportService $calamityReportService
    ) {}

    /**
     * Display a listing of all calamity reports
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'calamity_type', 'severity', 'search', 'sort', 'direction']);
        $perPage = $request->get('per_page', 15);

        $reports = $this->calamityReportService->getStaffReports($filters, $perPage);
        $statistics = $this->calamityReportService->getStatistics();

        return Inertia::render('Staff/Calamity/Index', [
            'reports' => [
                'data' => $reports->items(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'from' => $reports->firstItem(),
                'to' => $reports->lastItem(),
            ],
            'filters' => $filters,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Display the map view with all active reports
     */
    public function map(Request $request): Response
    {
        $activeReports = $this->calamityReportService->getActiveReportsForMap();
        $statistics = $this->calamityReportService->getStatistics();

        // Get resident locations (blue)
        $residentLocations = \App\Models\User::where('role', 'resident')
            ->where('location_shared', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('id', 'name', 'first_name', 'last_name', 'latitude', 'longitude', 'location_updated_at', 'purok')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->name ?? ($resident->first_name . ' ' . $resident->last_name),
                    'latitude' => (float) $resident->latitude,
                    'longitude' => (float) $resident->longitude,
                    'purok' => $resident->purok,
                    'updated_at' => $resident->location_updated_at?->toIso8601String(),
                ];
            });

        // Get staff locations (green)
        $user = $request->user();
        $staffLocations = \App\Models\User::where('role', 'staff')
            ->where('location_shared', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'first_name', 'last_name', 'latitude', 'longitude', 'location_updated_at')
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name ?? ($staff->first_name . ' ' . $staff->last_name),
                    'latitude' => (float) $staff->latitude,
                    'longitude' => (float) $staff->longitude,
                    'updated_at' => $staff->location_updated_at?->toIso8601String(),
                ];
            });

        // Get current staff member's location
        $currentUserLocation = null;
        if ($user->latitude && $user->longitude) {
            $currentUserLocation = [
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
            ];
        }

        return Inertia::render('Staff/Calamity/Map', [
            'activeReports' => $activeReports,
            'statistics' => $statistics,
            'residentLocations' => $residentLocations,
            'staffLocations' => $staffLocations,
            'currentUserLocation' => $currentUserLocation,
        ]);
    }

    /**
     * Display the specified calamity report
     */
    public function show(CalamityReport $calamityReport): Response
    {
        $calamityReport->load(['resident', 'staff']);

        return Inertia::render('Staff/Calamity/Show', [
            'report' => $calamityReport,
        ]);
    }

    /**
     * Update the specified calamity report
     */
    public function update(Request $request, CalamityReport $calamityReport): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,acknowledged,in_progress,assisted,resolved'],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
            'assistance_provided' => ['nullable', 'string', 'max:2000'],
            'staff_id' => ['nullable', 'exists:users,id'],
        ]);

        $this->calamityReportService->update($calamityReport, $validated, $request->user());

        return redirect()->route('staff.calamity.show', $calamityReport)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Get active reports for map (API endpoint)
     */
    public function getActiveReports(): \Illuminate\Http\JsonResponse
    {
        $reports = $this->calamityReportService->getActiveReportsForMap();

        return response()->json($reports);
    }
}
