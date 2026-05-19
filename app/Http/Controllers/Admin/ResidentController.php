<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ResidentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::where('role', 'resident');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($purok = $request->get('purok')) {
            $query->where('purok', 'like', "%{$purok}%");
        }

        $residents = $query->latest()
            ->paginate(20)
            ->through(fn ($u) => [
                'id'                    => $u->id,
                'name'                  => $u->name,
                'email'                 => $u->email,
                'phone'                 => $u->phone,
                'purok'                 => $u->purok,
                'sex'                   => $u->sex,
                'civil_status'          => $u->civil_status,
                'profile_completed_at'  => $u->profile_completed_at
                    ? Carbon::parse($u->profile_completed_at)->format('M d, Y')
                    : null,
                'created_at'            => $u->created_at?->format('M d, Y') ?? '—',
            ]);

        $filters = $request->only(['search', 'purok']);

        return Inertia::render('admin/Residents/Index', compact('residents', 'filters'));
    }
}
