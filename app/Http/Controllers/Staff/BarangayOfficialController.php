<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BarangayOfficial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BarangayOfficialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $officials = BarangayOfficial::ordered()->get();

        return Inertia::render('Staff/BarangayOfficials/Index', [
            'officials' => $officials
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Staff/BarangayOfficials/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'email' => 'nullable|email|unique:barangay_officials,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'civil_status' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'biography' => 'nullable|string',
            'term_start' => 'nullable|integer|min:2000|max:2050',
            'term_end' => 'nullable|integer|min:2000|max:2050|gte:term_start',
            'is_active' => 'boolean',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('barangay-officials', 'public');
        }

        // Auto-assign sort order based on position hierarchy
        $validated['sort_order'] = $this->getSortOrderForPosition($validated['position']);

        BarangayOfficial::create($validated);

        return redirect()->route('staff.barangay-officials.index')
            ->with('success', 'Barangay official created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BarangayOfficial $barangayOfficial)
    {
        return Inertia::render('Staff/BarangayOfficials/Show', [
            'official' => $barangayOfficial
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BarangayOfficial $barangayOfficial)
    {
        return Inertia::render('Staff/BarangayOfficials/Edit', [
            'official' => $barangayOfficial
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BarangayOfficial $barangayOfficial)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'email' => 'nullable|email|unique:barangay_officials,email,' . $barangayOfficial->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'civil_status' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'biography' => 'nullable|string',
            'term_start' => 'nullable|integer|min:2000|max:2050',
            'term_end' => 'nullable|integer|min:2000|max:2050|gte:term_start',
            'is_active' => 'boolean',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($barangayOfficial->photo) {
                Storage::disk('public')->delete($barangayOfficial->photo);
            }
            $validated['photo'] = $request->file('photo')->store('barangay-officials', 'public');
        }

        // Auto-assign sort order based on position hierarchy
        $validated['sort_order'] = $this->getSortOrderForPosition($validated['position']);

        $barangayOfficial->update($validated);

        return redirect()->route('staff.barangay-officials.index')
            ->with('success', 'Barangay official updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BarangayOfficial $barangayOfficial)
    {
        // Delete photo if exists
        if ($barangayOfficial->photo) {
            Storage::disk('public')->delete($barangayOfficial->photo);
        }

        $barangayOfficial->delete();

        return redirect()->route('staff.barangay-officials.index')
            ->with('success', 'Barangay official deleted successfully.');
    }

    /**
     * Get sort order for position based on hierarchy
     */
    private function getSortOrderForPosition(string $position): int
    {
        $positionHierarchy = [
            'Captain' => 1,
            'Vice Captain' => 2,
            'Secretary' => 3,
            'Treasurer' => 4,
            'Councilor' => 5,
            'SK Chairman' => 6,
            'SK Councilor' => 7,
            'Other' => 8,
        ];

        return $positionHierarchy[$position] ?? 9;
    }
}
