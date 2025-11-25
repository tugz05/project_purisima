<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\ResidentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResidentController extends Controller
{
    public function __construct(
        private ResidentService $residentService
    ) {}

    /**
     * Display a listing of all residents
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'purok', 'profile_completed', 'sort', 'direction']);
        $perPage = $request->get('per_page', 15);

        $residents = $this->residentService->getResidents($filters, $perPage);
        $statistics = $this->residentService->getStatistics();
        $puroks = $this->residentService->getPuroks();

        return Inertia::render('Staff/Residents/Index', [
            'residents' => [
                'data' => $residents->items(),
                'current_page' => $residents->currentPage(),
                'last_page' => $residents->lastPage(),
                'per_page' => $residents->perPage(),
                'total' => $residents->total(),
                'from' => $residents->firstItem(),
                'to' => $residents->lastItem(),
            ],
            'filters' => $filters,
            'statistics' => $statistics,
            'puroks' => $puroks,
        ]);
    }

}

