<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\HouseholdMember;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $householdMembers = $user->householdMembers()->orderBy('created_at', 'desc')->get();

        return Inertia::render('resident/HouseholdMembers/Index', [
            'householdMembers' => $householdMembers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('resident/HouseholdMembers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'relationship' => 'required|string|in:self,spouse,child,parent,sibling,grandparent,grandchild,other',
            'birth_date' => 'nullable|date',
            'sex' => 'nullable|in:male,female,other',
            'civil_status' => 'nullable|in:single,married,widowed,separated,divorced,other',
            'occupation' => 'nullable|string|max:255',
            'educational_attainment' => 'nullable|string|max:255',
            'is_working' => 'boolean',
            'is_student' => 'boolean',
            'is_senior_citizen' => 'boolean',
            'is_pwd' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $householdMember = $user->householdMembers()->create($validated);

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(HouseholdMember $householdMember): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Ensure the household member belongs to the authenticated user
        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('resident/HouseholdMembers/Show', [
            'householdMember' => $householdMember,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HouseholdMember $householdMember): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Ensure the household member belongs to the authenticated user
        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('resident/HouseholdMembers/Edit', [
            'householdMember' => $householdMember,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HouseholdMember $householdMember): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Ensure the household member belongs to the authenticated user
        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'relationship' => 'required|string|in:self,spouse,child,parent,sibling,grandparent,grandchild,other',
            'birth_date' => 'nullable|date',
            'sex' => 'nullable|in:male,female,other',
            'civil_status' => 'nullable|in:single,married,widowed,separated,divorced,other',
            'occupation' => 'nullable|string|max:255',
            'educational_attainment' => 'nullable|string|max:255',
            'is_working' => 'boolean',
            'is_student' => 'boolean',
            'is_senior_citizen' => 'boolean',
            'is_pwd' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $householdMember->update($validated);

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HouseholdMember $householdMember): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Ensure the household member belongs to the authenticated user
        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $householdMember->delete();

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member deleted successfully.');
    }
}
