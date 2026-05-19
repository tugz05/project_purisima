<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $txQuery = fn () => $user->transactions();

        $stats = [
            'total'       => $txQuery()->count(),
            'pending'     => $txQuery()->where('status', 'pending')->count(),
            'in_progress' => $txQuery()->where('status', 'in_progress')->count(),
            'completed'   => $txQuery()->where('status', 'completed')->count(),
            'rejected'    => $txQuery()->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];

        $recentTransactions = $txQuery()
            ->with('documentType:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($t) => [
                'id'                 => $t->id,
                'transaction_id'     => $t->transaction_id,
                'title'              => $t->title,
                'status'             => $t->status,
                'payment_status'     => $t->payment_status,
                'document_type_name' => $t->documentType?->name,
                'created_at'         => $t->created_at?->toIso8601String(),
            ]);

        $announcements = Announcement::published()
            ->ordered()
            ->take(3)
            ->get(['id', 'title', 'type', 'priority', 'published_at', 'content', 'is_featured'])
            ->map(fn ($a) => [
                'id'           => $a->id,
                'title'        => $a->title,
                'type'         => $a->type,
                'priority'     => $a->priority,
                'is_featured'  => $a->is_featured,
                'published_at' => $a->published_at?->toIso8601String(),
                'excerpt'      => Str::limit(strip_tags($a->content ?? ''), 100),
            ]);

        return Inertia::render('resident/Dashboard', [
            'stats'              => $stats,
            'recentTransactions' => $recentTransactions,
            'announcements'      => $announcements,
            'memberSince'        => $user->created_at?->format('M Y'),
        ]);
    }
}
