<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        $staff = User::where('role', 'staff')
            ->with('assignedDocumentTypes:id,name')
            ->latest()
            ->get(['id', 'first_name', 'last_name', 'name', 'email', 'phone', 'created_at'])
            ->map(fn ($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'phone'          => $u->phone,
                'created_at'     => $u->created_at?->format('M d, Y') ?? '—',
                'document_types' => $u->assignedDocumentTypes->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]),
            ]);

        $documentTypes = DocumentType::active()->ordered()->get(['id', 'name']);

        return Inertia::render('admin/Staff/Index', compact('staff', 'documentTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:20',
            'password'         => 'required|string|min:8|confirmed',
            'document_type_ids' => 'nullable|array',
            'document_type_ids.*' => 'exists:document_types,id',
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'role'       => 'staff',
        ]);

        if (!empty($data['document_type_ids'])) {
            $user->assignedDocumentTypes()->sync($data['document_type_ids']);
        }

        return back()->with('success', 'Staff account created successfully.');
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->role !== 'staff', 403);

        $data = $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => ['required', 'email', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone'            => 'nullable|string|max:20',
            'password'         => 'nullable|string|min:8|confirmed',
            'document_type_ids' => 'nullable|array',
            'document_type_ids.*' => 'exists:document_types,id',
        ]);

        $staff->fill([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $staff->password = Hash::make($data['password']);
        }

        $staff->save();

        $staff->assignedDocumentTypes()->sync($data['document_type_ids'] ?? []);

        return back()->with('success', 'Staff account updated successfully.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        abort_if($staff->role !== 'staff', 403);

        $staff->delete();

        return back()->with('success', 'Staff account removed.');
    }
}
