<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalamityReportRequest;
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
     * Display a listing of the resident's calamity reports
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'sort', 'direction']);
        $reports = $this->calamityReportService->getResidentReports($request->user(), $filters);

        return Inertia::render('resident/calamity/Index', [
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
        ]);
    }

    /**
     * Store a newly created calamity report
     */
    public function store(CalamityReportRequest $request): RedirectResponse
    {
        $report = $this->calamityReportService->create($request->validated(), $request->user());

        return redirect()->route('resident.calamity.index')
            ->with('success', 'Calamity report submitted successfully. Help is on the way!');
    }

    /**
     * Display the specified calamity report
     */
    public function show(CalamityReport $calamityReport): Response
    {
        $this->authorize('view', $calamityReport);

        $calamityReport->load(['staff']);

        return Inertia::render('resident/calamity/Show', [
            'report' => $calamityReport,
        ]);
    }

    /**
     * Update location for an existing report
     */
    public function updateLocation(Request $request, CalamityReport $calamityReport): RedirectResponse
    {
        $this->authorize('update', $calamityReport);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->calamityReportService->update($calamityReport, $validated);

        return redirect()->back()
            ->with('success', 'Location updated successfully.');
    }
}
