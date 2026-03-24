<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\UpdateManagedUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function assignableRoles(): array
    {
        return [
            ['value' => 'resident', 'label' => 'Resident'],
            ['value' => 'staff', 'label' => 'Staff'],
            ['value' => 'admin', 'label' => 'Admin'],
            ['value' => 'enforcer', 'label' => 'Enforcer'],
            ['value' => 'superadmin', 'label' => 'Superadmin'],
        ];
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $query = User::query()->orderBy('name');

        if (! empty($filters['search'])) {
            $term = (string) $filters['search'];
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        $users = $query->paginate($perPage)->withQueryString();

        $roleCounts = User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role')
            ->all();

        return Inertia::render('superadmin/Users/Index', [
            'users' => [
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => $filters,
            'roleCounts' => $roleCounts,
            'assignableRoles' => self::assignableRoles(),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('superadmin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
            'assignableRoles' => self::assignableRoles(),
        ]);
    }

    public function update(UpdateManagedUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()
            ->route('superadmin.users.index')
            ->with('success', 'User updated successfully.');
    }
}
