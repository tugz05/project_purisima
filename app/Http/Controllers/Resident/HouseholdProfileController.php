<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\HouseholdProfile;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdProfileController extends Controller
{
    /**
     * Display the household profile.
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $householdProfile = $user->householdProfile()->with('familyMembers')->first();

        return Inertia::render('resident/HouseholdProfile/Index', [
            'householdProfile' => $householdProfile,
            'user' => $user,
        ]);
    }

    /**
     * Show the form for creating a new household profile.
     */
    public function create(): Response|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $householdProfile = $user->householdProfile;

        if ($householdProfile) {
            return redirect()->route('resident.household-profile.index');
        }

        return Inertia::render('resident/HouseholdProfile/Create', [
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created household profile.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if household profile already exists
        if ($user->householdProfile) {
            return redirect()->route('resident.household-profile.index')
                ->with('error', 'Household profile already exists.');
        }

        $validated = $request->validate([
            'household_head_name' => 'required|string|max:255',
            'household_head_relationship' => 'required|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'income_source' => 'nullable|string|max:255',
            'income_source_details' => 'nullable|string|max:1000',
            'total_family_members' => 'required|integer|min:1',
            'working_members' => 'required|integer|min:0',
            'dependent_members' => 'required|integer|min:0',
            'housing_type' => 'nullable|string|max:255',
            'housing_details' => 'nullable|string|max:1000',
            'has_vehicle' => 'boolean',
            'vehicle_details' => 'nullable|string|max:1000',
            'has_livestock' => 'boolean',
            'livestock_details' => 'nullable|string|max:1000',
            'additional_notes' => 'nullable|string|max:2000',
            'family_members' => 'required|array|min:1',
            'family_members.*.first_name' => 'required|string|max:255',
            'family_members.*.middle_name' => 'nullable|string|max:255',
            'family_members.*.last_name' => 'required|string|max:255',
            'family_members.*.relationship_to_head' => 'required|string|max:255',
            'family_members.*.birth_date' => 'nullable|date',
            'family_members.*.sex' => 'nullable|string|in:male,female,other',
            'family_members.*.civil_status' => 'nullable|string|in:single,married,widowed,separated,other',
            'family_members.*.educational_attainment' => 'nullable|string|in:none,elementary,high_school,college,graduate,other',
            'family_members.*.occupation' => 'nullable|string|max:255',
            'family_members.*.employment_status' => 'nullable|string|in:employed,unemployed,student,retired,housewife,other',
            'family_members.*.monthly_income' => 'nullable|numeric|min:0',
            'family_members.*.is_working' => 'boolean',
            'family_members.*.is_student' => 'boolean',
            'family_members.*.is_senior_citizen' => 'boolean',
            'family_members.*.is_pwd' => 'boolean',
            'family_members.*.disability_details' => 'nullable|string|max:1000',
            'family_members.*.additional_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Create household profile
            $householdProfile = $user->householdProfile()->create([
                'household_head_name' => $validated['household_head_name'],
                'household_head_relationship' => $validated['household_head_relationship'],
                'monthly_income' => $validated['monthly_income'],
                'income_source' => $validated['income_source'],
                'income_source_details' => $validated['income_source_details'],
                'total_family_members' => $validated['total_family_members'],
                'working_members' => $validated['working_members'],
                'dependent_members' => $validated['dependent_members'],
                'housing_type' => $validated['housing_type'],
                'housing_details' => $validated['housing_details'],
                'has_vehicle' => $validated['has_vehicle'] ?? false,
                'vehicle_details' => $validated['vehicle_details'],
                'has_livestock' => $validated['has_livestock'] ?? false,
                'livestock_details' => $validated['livestock_details'],
                'additional_notes' => $validated['additional_notes'],
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // Create family members
            foreach ($validated['family_members'] as $memberData) {
                $householdProfile->familyMembers()->create($memberData);
            }

            DB::commit();

            return redirect()->route('resident.household-profile.index')
                ->with('success', 'Household profile created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create household profile. Please try again.']);
        }
    }

    /**
     * Show the form for editing the household profile.
     */
    public function edit(HouseholdProfile $householdProfile): Response
    {
        $user = Auth::user();

        // Ensure the household profile belongs to the authenticated user
        if ($householdProfile->user_id !== $user->id) {
            abort(403);
        }

        $householdProfile->load('familyMembers');

        return Inertia::render('resident/HouseholdProfile/Edit', [
            'householdProfile' => $householdProfile,
            'user' => $user,
        ]);
    }

    /**
     * Update the household profile.
     */
    public function update(Request $request, HouseholdProfile $householdProfile)
    {
        $user = Auth::user();

        // Ensure the household profile belongs to the authenticated user
        if ($householdProfile->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'household_head_name' => 'required|string|max:255',
            'household_head_relationship' => 'required|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'income_source' => 'nullable|string|max:255',
            'income_source_details' => 'nullable|string|max:1000',
            'total_family_members' => 'required|integer|min:1',
            'working_members' => 'required|integer|min:0',
            'dependent_members' => 'required|integer|min:0',
            'housing_type' => 'nullable|string|max:255',
            'housing_details' => 'nullable|string|max:1000',
            'has_vehicle' => 'boolean',
            'vehicle_details' => 'nullable|string|max:1000',
            'has_livestock' => 'boolean',
            'livestock_details' => 'nullable|string|max:1000',
            'additional_notes' => 'nullable|string|max:2000',
            'family_members' => 'required|array|min:1',
            'family_members.*.id' => 'nullable|integer|exists:family_members,id',
            'family_members.*.first_name' => 'required|string|max:255',
            'family_members.*.middle_name' => 'nullable|string|max:255',
            'family_members.*.last_name' => 'required|string|max:255',
            'family_members.*.relationship_to_head' => 'required|string|max:255',
            'family_members.*.birth_date' => 'nullable|date',
            'family_members.*.sex' => 'nullable|string|in:male,female,other',
            'family_members.*.civil_status' => 'nullable|string|in:single,married,widowed,separated,other',
            'family_members.*.educational_attainment' => 'nullable|string|in:none,elementary,high_school,college,graduate,other',
            'family_members.*.occupation' => 'nullable|string|max:255',
            'family_members.*.employment_status' => 'nullable|string|in:employed,unemployed,student,retired,housewife,other',
            'family_members.*.monthly_income' => 'nullable|numeric|min:0',
            'family_members.*.is_working' => 'boolean',
            'family_members.*.is_student' => 'boolean',
            'family_members.*.is_senior_citizen' => 'boolean',
            'family_members.*.is_pwd' => 'boolean',
            'family_members.*.disability_details' => 'nullable|string|max:1000',
            'family_members.*.additional_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Update household profile
            $householdProfile->update([
                'household_head_name' => $validated['household_head_name'],
                'household_head_relationship' => $validated['household_head_relationship'],
                'monthly_income' => $validated['monthly_income'],
                'income_source' => $validated['income_source'],
                'income_source_details' => $validated['income_source_details'],
                'total_family_members' => $validated['total_family_members'],
                'working_members' => $validated['working_members'],
                'dependent_members' => $validated['dependent_members'],
                'housing_type' => $validated['housing_type'],
                'housing_details' => $validated['housing_details'],
                'has_vehicle' => $validated['has_vehicle'] ?? false,
                'vehicle_details' => $validated['vehicle_details'],
                'has_livestock' => $validated['has_livestock'] ?? false,
                'livestock_details' => $validated['livestock_details'],
                'additional_notes' => $validated['additional_notes'],
            ]);

            // Get existing family member IDs
            $existingIds = $householdProfile->familyMembers()->pluck('id')->toArray();
            $submittedIds = collect($validated['family_members'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete removed family members
            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                FamilyMember::whereIn('id', $idsToDelete)->delete();
            }

            // Update or create family members
            foreach ($validated['family_members'] as $memberData) {
                if (isset($memberData['id']) && $memberData['id']) {
                    // Update existing member
                    $member = FamilyMember::find($memberData['id']);
                    if ($member) {
                        unset($memberData['id']);
                        $member->update($memberData);
                    }
                } else {
                    // Create new member
                    unset($memberData['id']);
                    $householdProfile->familyMembers()->create($memberData);
                }
            }

            DB::commit();

            return redirect()->route('resident.household-profile.index')
                ->with('success', 'Household profile updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update household profile. Please try again.']);
        }
    }

    /**
     * Remove the household profile.
     */
    public function destroy(HouseholdProfile $householdProfile)
    {
        $user = Auth::user();

        // Ensure the household profile belongs to the authenticated user
        if ($householdProfile->user_id !== $user->id) {
            abort(403);
        }

        try {
            $householdProfile->delete();

            return redirect()->route('resident.household-profile.create')
                ->with('success', 'Household profile deleted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete household profile. Please try again.']);
        }
    }
}
