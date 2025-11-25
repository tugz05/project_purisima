<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Update the staff's location
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_shared' => ['boolean'],
        ]);

        $user = Auth::user();
        $user->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'location_shared' => $validated['location_shared'] ?? true,
            'location_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
        ]);
    }

    /**
     * Get resident locations for staff map view
     */
    public function getResidentLocations(): JsonResponse
    {
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

        return response()->json($residentLocations);
    }
}
