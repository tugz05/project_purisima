<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncidentReportRequest;
use App\Models\IncidentReport;
use App\Services\IncidentReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private IncidentReportService $incidentReportService
    ) {}

    /**
     * Display a listing of the resident's incident reports
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'sort', 'direction']);
        $reports = $this->incidentReportService->getResidentReports($request->user(), $filters);

        return Inertia::render('resident/incidents/Index', [
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
     * Store a newly created incident report
     */
    public function store(IncidentReportRequest $request): RedirectResponse
    {
        $report = $this->incidentReportService->create($request->validated(), $request->user());

        return redirect()->route('resident.incidents.index')
            ->with('success', 'Incident report submitted successfully. Help is on the way!');
    }

    /**
     * Display the specified incident report
     */
    public function show(IncidentReport $incidentReport): Response
    {
        $this->authorize('view', $incidentReport);

        $incidentReport->load(['staff']);

        return Inertia::render('resident/incidents/Show', [
            'report' => $incidentReport,
        ]);
    }

    /**
     * Update location for an existing report
     */
    public function updateLocation(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        $this->authorize('update', $incidentReport);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->incidentReportService->update($incidentReport, $validated);

        return redirect()->back()
            ->with('success', 'Location updated successfully.');
    }
}
