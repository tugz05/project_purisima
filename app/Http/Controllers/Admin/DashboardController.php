<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Purok;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_residents' => User::where('role', 'resident')->count(),
            'total_staff'     => User::where('role', 'staff')->count(),
            'total_puroks'    => Purok::where('is_active', true)->count(),
            'total_tx'        => Transaction::count(),
            'pending_tx'      => Transaction::where('status', 'pending')->count(),
            'completed_tx'    => Transaction::where('status', 'completed')->count(),
            'monthly_revenue' => (float) Transaction::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid'),
        ];

        $recentStaff = User::where('role', 'staff')
            ->latest()
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'name', 'email', 'created_at'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'created_at' => $u->created_at?->format('M d, Y') ?? '—',
            ]);

        $recentResidents = User::where('role', 'resident')
            ->latest()
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'name', 'email', 'purok', 'created_at'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'purok'      => $u->purok,
                'created_at' => $u->created_at?->format('M d, Y') ?? '—',
            ]);

        return Inertia::render('admin/Dashboard', compact('stats', 'recentStaff', 'recentResidents'));
    }
}
