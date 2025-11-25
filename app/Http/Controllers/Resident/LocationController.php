<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Update the resident's location
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_shared' => ['boolean'],
        ]);

        $user = Auth::user();
        
        // Ensure user is a resident
        if ($user->role !== 'resident') {
            abort(403, 'This action is unauthorized.');
        }
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
     * Get map view with staff and resident locations
     */
    public function map(): Response
    {
        $user = Auth::user();
        
        // Ensure user is a resident
        if ($user->role !== 'resident') {
            abort(403, 'This action is unauthorized.');
        }
        
        // Get staff locations (green)
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
                    'type' => 'staff',
                    'updated_at' => $staff->location_updated_at?->toIso8601String(),
                ];
            });

        // Get other residents' locations (blue)
        $residentLocations = \App\Models\User::where('role', 'resident')
            ->where('location_shared', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'first_name', 'last_name', 'latitude', 'longitude', 'location_updated_at')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->name ?? ($resident->first_name . ' ' . $resident->last_name),
                    'latitude' => (float) $resident->latitude,
                    'longitude' => (float) $resident->longitude,
                    'type' => 'resident',
                    'updated_at' => $resident->location_updated_at?->toIso8601String(),
                ];
            });

        // Get current user's location
        $currentUserLocation = null;
        if ($user->latitude && $user->longitude) {
            $currentUserLocation = [
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
            ];
        }

        return Inertia::render('resident/calamity/Map', [
            'staffLocations' => $staffLocations,
            'residentLocations' => $residentLocations,
            'currentUserLocation' => $currentUserLocation,
        ]);
    }
}
