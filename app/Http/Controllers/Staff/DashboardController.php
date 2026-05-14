<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $this->validPeriod($request->get('period', 'monthly'));
        [$start, $end, $prevStart, $prevEnd] = $this->dateRange($period);

        $current  = $this->periodStats($start, $end);
        $previous = $this->periodStats($prevStart, $prevEnd);

        return Inertia::render('Staff/Dashboard', [
            'period'             => $period,
            'meta'               => $this->buildMeta($period, $start, $end),
            'stats'              => $this->buildStats($current, $previous),
            'trends'             => $this->trendData($period, $start, $end),
            'documentTypes'      => $this->documentTypeBreakdown($start, $end),
            'paymentMethods'     => $this->paymentMethodBreakdown($start, $end),
            'recentTransactions' => $this->recentTransactions(8),
        ]);
    }

    public function printReport(Request $request): Response
    {
        $period = $this->validPeriod($request->get('period', 'monthly'));
        [$start, $end] = $this->dateRange($period);
        $stats = $this->periodStats($start, $end);

        return Inertia::render('Staff/reports/PrintReport', [
            'period'             => $period,
            'meta'               => $this->buildMeta($period, $start, $end),
            'stats'              => $stats,
            'completionRate'     => $stats['total'] > 0
                ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0,
            'documentTypes'      => $this->documentTypeBreakdown($start, $end),
            'paymentMethods'     => $this->paymentMethodBreakdown($start, $end),
            'recentTransactions' => $this->recentTransactions(20),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validPeriod(mixed $raw): string
    {
        return in_array($raw, ['weekly', 'monthly', 'annually'], true) ? (string) $raw : 'monthly';
    }

    private function dateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'weekly'   => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'annually' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            default    => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
        };
    }

    private function periodStats(Carbon $start, Carbon $end): array
    {
        $q = fn () => Transaction::whereBetween('created_at', [$start, $end]);

        return [
            'total'           => $q()->count(),
            'pending'         => $q()->where('status', 'pending')->count(),
            'in_progress'     => $q()->where('status', 'in_progress')->count(),
            'completed'       => $q()->where('status', 'completed')->count(),
            'rejected'        => $q()->where('status', 'rejected')->count(),
            'revenue'         => (float) ($q()->where('payment_status', 'paid')->sum('amount_paid') ?? 0),
            'pending_revenue' => (float) ($q()->where('payment_status', 'pending')->sum('fee_amount') ?? 0),
        ];
    }

    private function buildStats(array $cur, array $prev): array
    {
        $curActive  = $cur['pending'] + $cur['in_progress'];
        $prevActive = $prev['pending'] + $prev['in_progress'];

        return [
            'total_transactions' => [
                'current' => $cur['total'],
                'change'  => $this->pct($prev['total'], $cur['total']),
            ],
            'revenue' => [
                'current' => $cur['revenue'],
                'change'  => $this->pct($prev['revenue'], $cur['revenue']),
            ],
            'completed' => [
                'current' => $cur['completed'],
                'change'  => $this->pct($prev['completed'], $cur['completed']),
            ],
            'pending' => [
                'current' => $curActive,
                'change'  => $this->pct($prevActive, $curActive),
            ],
            'completion_rate'  => $cur['total'] > 0
                ? round(($cur['completed'] / $cur['total']) * 100, 1) : 0,
            'total_residents'  => User::where('role', 'resident')->count(),
            'status_breakdown' => [
                'pending'     => $cur['pending'],
                'in_progress' => $cur['in_progress'],
                'completed'   => $cur['completed'],
                'rejected'    => $cur['rejected'],
            ],
        ];
    }

    private function trendData(string $period, Carbon $start, Carbon $end): array
    {
        $case = 'SUM(CASE WHEN payment_status = "paid" THEN COALESCE(amount_paid,0) ELSE 0 END) as rev';

        if ($period === 'annually') {
            $rows = Transaction::whereBetween('created_at', [$start, $end])
                ->selectRaw("MONTH(created_at) as k, COUNT(*) as cnt, {$case}")
                ->groupByRaw('MONTH(created_at)')->get()->keyBy('k');

            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $counts = $revenues = [];
            for ($m = 1; $m <= 12; $m++) {
                $counts[]   = (int)   ($rows->get($m)?->cnt ?? 0);
                $revenues[] = (float) ($rows->get($m)?->rev ?? 0);
            }
        } elseif ($period === 'weekly') {
            $rows = Transaction::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as k, COUNT(*) as cnt, {$case}")
                ->groupByRaw('DATE(created_at)')->get()->keyBy('k');

            $labels = $counts = $revenues = [];
            foreach (CarbonPeriod::create($start, '1 day', $end) as $day) {
                $key        = $day->format('Y-m-d');
                $labels[]   = $day->format('D');
                $counts[]   = (int)   ($rows->get($key)?->cnt ?? 0);
                $revenues[] = (float) ($rows->get($key)?->rev ?? 0);
            }
        } else {
            $rows = Transaction::whereBetween('created_at', [$start, $end])
                ->selectRaw("DAY(created_at) as k, COUNT(*) as cnt, {$case}")
                ->groupByRaw('DAY(created_at)')->get()->keyBy('k');

            $labels = $counts = $revenues = [];
            for ($d = 1; $d <= $end->day; $d++) {
                $labels[]   = (string) $d;
                $counts[]   = (int)   ($rows->get($d)?->cnt ?? 0);
                $revenues[] = (float) ($rows->get($d)?->rev ?? 0);
            }
        }

        return compact('labels', 'counts', 'revenues');
    }

    private function documentTypeBreakdown(Carbon $start, Carbon $end): array
    {
        return Transaction::whereBetween('transactions.created_at', [$start, $end])
            ->leftJoin('document_types', 'transactions.document_type_id', '=', 'document_types.id')
            ->selectRaw('COALESCE(document_types.name, transactions.type, "Unknown") as label, COUNT(*) as count')
            ->groupByRaw('COALESCE(document_types.name, transactions.type, "Unknown")')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count])
            ->toArray();
    }

    private function paymentMethodBreakdown(Carbon $start, Carbon $end): array
    {
        $display = [
            'cash'          => 'Cash',
            'gcash'         => 'GCash',
            'paymaya'       => 'PayMaya',
            'bank_transfer' => 'Bank Transfer',
            'check'         => 'Check',
        ];

        return Transaction::whereBetween('created_at', [$start, $end])
            ->whereNotNull('payment_method')
            ->where('payment_status', 'paid')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(COALESCE(amount_paid,0)) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => [
                'method' => $display[$r->payment_method] ?? ucfirst(str_replace('_', ' ', $r->payment_method)),
                'count'  => (int)   $r->count,
                'total'  => (float) $r->total,
            ])
            ->toArray();
    }

    private function recentTransactions(int $limit = 8): array
    {
        return Transaction::with(['resident:id,first_name,last_name,name', 'documentType:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'transaction_id' => $t->transaction_id,
                'resident_name'  => $t->resident?->name ?? '—',
                'document_type'  => $t->documentType?->name ?? ($t->type ?? '—'),
                'status'         => $t->status,
                'payment_status' => $t->payment_status,
                'fee_amount'     => (float) $t->fee_amount,
                'created_at'     => $t->created_at->format('M d, Y'),
            ])
            ->toArray();
    }

    private function buildMeta(string $period, Carbon $start, Carbon $end): array
    {
        return [
            'period_label' => match ($period) {
                'weekly'   => 'This Week',
                'annually' => 'This Year',
                default    => 'This Month',
            },
            'date_range'   => $start->format('M d, Y').' – '.$end->format('M d, Y'),
            'generated_at' => now()->format('F d, Y g:i A'),
        ];
    }

    private function pct(float $prev, float $cur): ?float
    {
        if ($prev == 0) {
            return $cur > 0 ? 100.0 : null;
        }

        return round((($cur - $prev) / $prev) * 100, 1);
    }
}
