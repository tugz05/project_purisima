<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyRegistrationLocationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationLocationVerificationController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('auth/VerifyRegistrationLocation', [
            'municipalityLabel' => 'Tago, Surigao del Sur',
        ]);
    }

    public function store(VerifyRegistrationLocationRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'location_shared' => true,
            'location_updated_at' => now(),
            'registration_geo_verified_at' => now(),
        ])->save();

        if (empty($user->profile_completed_at)) {
            return redirect()->route('resident.onboarding.show')
                ->with('success', 'Location verified. Please complete your profile.');
        }

        return redirect()->route('resident.dashboard');
    }
}
