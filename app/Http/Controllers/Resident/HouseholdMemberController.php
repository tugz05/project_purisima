<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HouseholdMemberController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $householdMembers = $user->householdMembers()
            ->with('linkedUser:id,name,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        // Invitations sent TO this user by other residents
        $incomingInvitations = HouseholdMember::where('linked_user_id', $user->id)
            ->where('invitation_status', 'pending')
            ->with('user:id,name,first_name,last_name,purok')
            ->get()
            ->map(fn ($m) => [
                'id'           => $m->id,
                'inviter_name' => $m->user?->name ?? '—',
                'inviter_purok'=> $m->user?->purok,
                'relationship' => $m->relationship,
            ]);

        return Inertia::render('resident/HouseholdMembers/Index', [
            'householdMembers'    => $householdMembers,
            'incomingInvitations' => $incomingInvitations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('resident/HouseholdMembers/Create');
    }

    /**
     * Search registered residents by name (autocomplete API).
     *
     * Supports single-letter, partial, and multi-word queries.
     * Each whitespace-separated token must match at least one of:
     * first_name prefix, last_name prefix, middle_name prefix, suffix prefix, or name contains.
     * This lets "Juan D Santos" find "Juan D. Santos" across separate columns.
     */
    public function searchResidents(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Single query: exclude self + anyone already linked in this household
        $excludedIds = $user->householdMembers()
            ->whereNotNull('linked_user_id')
            ->pluck('linked_user_id')
            ->push($user->id)
            ->unique();

        // Split on whitespace, commas, or dots — filter empty tokens
        $tokens = array_values(array_filter(
            preg_split('/[\s,\.]+/', $q, -1, PREG_SPLIT_NO_EMPTY),
            fn ($t) => $t !== ''
        ));

        $query = User::where('role', 'resident')
            ->whereNotIn('id', $excludedIds);

        // AND across tokens: each token must match at least one name field
        foreach ($tokens as $token) {
            $query->where(function ($q) use ($token) {
                $q->where('first_name', 'like', "{$token}%")   // prefix — uses index
                  ->orWhere('last_name',   'like', "{$token}%")  // prefix — uses index
                  ->orWhere('middle_name', 'like', "{$token}%")  // prefix — uses index
                  ->orWhere('suffix',      'like', "{$token}%")
                  ->orWhere('name',        'like', "%{$token}%"); // fallback full scan
            });
        }

        $residents = $query
            ->orderByRaw('last_name ASC, first_name ASC')
            ->limit(10)
            ->get(['id', 'name', 'first_name', 'middle_name', 'last_name', 'suffix',
                   'purok', 'birth_date', 'sex', 'civil_status', 'occupation', 'educational_attainment']);

        return response()->json($residents->map(fn ($r) => [
            'id'                     => $r->id,
            'name'                   => $r->name,
            'purok'                  => $r->purok,
            'first_name'             => $r->first_name,
            'middle_name'            => $r->middle_name,
            'last_name'              => $r->last_name,
            'suffix'                 => $r->suffix,
            'birth_date'             => $r->birth_date,
            'sex'                    => $r->sex,
            'civil_status'           => $r->civil_status,
            'occupation'             => $r->occupation,
            'educational_attainment' => $r->educational_attainment,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($request->filled('linked_user_id')) {
            return $this->storeLinked($request, $user);
        }

        return $this->storeManual($request, $user);
    }

    private function storeLinked(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'linked_user_id' => ['required', 'integer', 'exists:users,id'],
            'relationship'   => ['required', 'string', 'in:self,spouse,child,parent,sibling,grandparent,grandchild,other'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['linked_user_id'] === $user->id) {
            return redirect()->back()->withErrors(['linked_user_id' => 'You cannot add yourself.']);
        }

        if ($user->householdMembers()->where('linked_user_id', $validated['linked_user_id'])->exists()) {
            return redirect()->back()->withErrors(['linked_user_id' => 'This resident is already in your household.']);
        }

        $linked = User::find($validated['linked_user_id']);

        $member = $user->householdMembers()->create([
            'linked_user_id'   => $linked->id,
            'first_name'       => $linked->first_name ?? $linked->name,
            'middle_name'      => $linked->middle_name,
            'last_name'        => $linked->last_name ?? '',
            'suffix'           => $linked->suffix,
            'relationship'     => $validated['relationship'],
            'notes'            => $validated['notes'] ?? null,
            'invitation_status'=> 'pending',
        ]);

        $this->notificationService->createNotification(
            $linked,
            'household_invitation',
            'Household Member Invitation',
            "{$user->name} has invited you to join their household as a {$validated['relationship']}. Please accept or decline.",
            [
                'household_member_id' => $member->id,
                'inviter_id'          => $user->id,
                'inviter_name'        => $user->name,
                'relationship'        => $validated['relationship'],
            ],
            'high',
            'household'
        );

        return redirect()->route('resident.household-members.index')
            ->with('success', "Invitation sent to {$linked->name}. They will be added once they accept.");
    }

    private function storeManual(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name'              => ['required', 'string', 'max:255'],
            'relationship'           => ['required', 'string', 'in:self,spouse,child,parent,sibling,grandparent,grandchild,other'],
            'birth_date'             => ['nullable', 'date'],
            'sex'                    => ['nullable', 'in:male,female,other'],
            'civil_status'           => ['nullable', 'in:single,married,widowed,separated,divorced,other'],
            'occupation'             => ['nullable', 'string', 'max:255'],
            'educational_attainment' => ['nullable', 'string', 'max:255'],
            'is_working'             => ['boolean'],
            'is_student'             => ['boolean'],
            'is_senior_citizen'      => ['boolean'],
            'is_pwd'                 => ['boolean'],
            'notes'                  => ['nullable', 'string'],
        ]);

        [$firstName, $middleName, $lastName] = $this->parseFullName($validated['full_name']);

        $user->householdMembers()->create([
            'first_name'             => $firstName,
            'middle_name'            => $middleName,
            'last_name'              => $lastName,
            'relationship'           => $validated['relationship'],
            'birth_date'             => $validated['birth_date'] ?? null,
            'sex'                    => $validated['sex'] ?? null,
            'civil_status'           => $validated['civil_status'] ?? null,
            'occupation'             => $validated['occupation'] ?? null,
            'educational_attainment' => $validated['educational_attainment'] ?? null,
            'is_working'             => $validated['is_working'] ?? false,
            'is_student'             => $validated['is_student'] ?? false,
            'is_senior_citizen'      => $validated['is_senior_citizen'] ?? false,
            'is_pwd'                 => $validated['is_pwd'] ?? false,
            'notes'                  => $validated['notes'] ?? null,
        ]);

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member added successfully.');
    }

    /**
     * Accept or decline a household invitation.
     * The current user must be the linked_user of the household member.
     */
    public function respondInvitation(Request $request, HouseholdMember $householdMember): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($householdMember->linked_user_id !== $user->id) {
            abort(403);
        }

        if ($householdMember->invitation_status !== 'pending') {
            return redirect()->route('resident.household-members.index')
                ->with('error', 'This invitation has already been responded to.');
        }

        $action = $request->validate(['action' => ['required', 'in:accept,decline']])['action'];

        $householdMember->update([
            'invitation_status' => $action === 'accept' ? 'accepted' : 'declined',
        ]);

        $inviter = $householdMember->user;
        if ($inviter) {
            $actionLabel = $action === 'accept' ? 'accepted' : 'declined';
            $this->notificationService->createNotification(
                $inviter,
                'household_invitation_response',
                'Household Invitation ' . ucfirst($actionLabel),
                "{$user->name} has {$actionLabel} your household invitation.",
                ['household_member_id' => $householdMember->id, 'action' => $action],
                'normal',
                'household'
            );
        }

        $msg = $action === 'accept'
            ? 'You have accepted the household invitation.'
            : 'You have declined the household invitation.';

        return redirect()->route('resident.household-members.index')->with('success', $msg);
    }

    public function show(HouseholdMember $householdMember): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('resident/HouseholdMembers/Show', [
            'householdMember' => $householdMember,
        ]);
    }

    public function edit(HouseholdMember $householdMember): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('resident/HouseholdMembers/Edit', [
            'householdMember' => $householdMember,
        ]);
    }

    public function update(Request $request, HouseholdMember $householdMember): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'first_name'             => 'required|string|max:255',
            'middle_name'            => 'nullable|string|max:255',
            'last_name'              => 'required|string|max:255',
            'suffix'                 => 'nullable|string|max:50',
            'relationship'           => 'required|string|in:self,spouse,child,parent,sibling,grandparent,grandchild,other',
            'birth_date'             => 'nullable|date',
            'sex'                    => 'nullable|in:male,female,other',
            'civil_status'           => 'nullable|in:single,married,widowed,separated,divorced,other',
            'occupation'             => 'nullable|string|max:255',
            'educational_attainment' => 'nullable|string|max:255',
            'is_working'             => 'boolean',
            'is_student'             => 'boolean',
            'is_senior_citizen'      => 'boolean',
            'is_pwd'                 => 'boolean',
            'notes'                  => 'nullable|string',
        ]);

        $householdMember->update($validated);

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member updated successfully.');
    }

    public function destroy(HouseholdMember $householdMember): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($householdMember->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $householdMember->delete();

        return redirect()->route('resident.household-members.index')
            ->with('success', 'Household member deleted successfully.');
    }

    /**
     * Parse "First [Middle] Last" → [first, middle|null, last].
     * Middle is everything between first and last word.
     */
    private function parseFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1) {
            return [$parts[0], null, $parts[0]];
        }

        $first  = array_shift($parts);
        $last   = array_pop($parts);
        $middle = count($parts) > 0 ? implode(' ', $parts) : null;

        return [$first, $middle, $last];
    }
}
