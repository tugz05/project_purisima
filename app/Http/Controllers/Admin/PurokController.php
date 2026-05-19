<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PurokController extends Controller
{
    public function index(): Response
    {
        $puroks = Purok::ordered()
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'is_active'   => $p->is_active,
                'sort_order'  => $p->sort_order,
                'resident_count' => User::where('role', 'resident')
                    ->where(function ($q) use ($p) {
                        $q->where('purok', $p->name)
                          ->orWhere('purok', 'like', '%'.$p->name.'%');
                    })->count(),
            ]);

        return Inertia::render('admin/Puroks/Index', compact('puroks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:puroks,name',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        // Auto-assign sort_order: newest purok gets the next available slot
        $data['sort_order'] = (Purok::max('sort_order') ?? 0) + 1;

        Purok::create($data);

        return back()->with('success', 'Purok created successfully.');
    }

    public function update(Request $request, Purok $purok): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('puroks', 'name')->ignore($purok->id)],
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $purok->update($data);

        return back()->with('success', 'Purok updated successfully.');
    }

    public function destroy(Purok $purok): RedirectResponse
    {
        $purok->delete();

        return back()->with('success', 'Purok deleted.');
    }
}
