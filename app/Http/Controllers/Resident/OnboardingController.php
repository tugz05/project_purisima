<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResidentProfileRequest;
use App\Services\ResidentProfileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('resident/Onboarding');
    }

    public function store(ResidentProfileRequest $request, ResidentProfileService $service): RedirectResponse
    {
        $service->complete($request->user(), $request->validated());

        return redirect()->route('resident.dashboard');
    }
}


