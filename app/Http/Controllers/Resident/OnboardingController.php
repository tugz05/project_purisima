<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResidentProfileRequest;
use App\Services\ResidentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->refresh();

        $existingPhotoUrl = $user !== null && is_string($user->photo_url) && trim($user->photo_url) !== ''
            ? trim($user->photo_url)
            : null;

        return Inertia::render('resident/Onboarding', [
            'existingPhotoUrl' => $existingPhotoUrl,
        ]);
    }

    public function store(ResidentProfileRequest $request, ResidentProfileService $service): RedirectResponse
    {
        $service->complete($request->user(), $request->validated());

        return redirect()->route('resident.dashboard');
    }
}
