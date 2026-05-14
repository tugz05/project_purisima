<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    BarElement, LineElement, PointElement, ArcElement,
    CategoryScale, LinearScale, Filler,
} from 'chart.js';
import { Bar, Line as LineChart, Doughnut } from 'vue-chartjs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    FileText, DollarSign, CheckCircle, Clock,
    TrendingUp, TrendingDown, Minus,
    Printer, FileType, Users, CreditCard, FileCheck,
    ArrowRight,
} from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Link } from '@inertiajs/vue3';

ChartJS.register(
    Title, Tooltip, Legend,
    BarElement, LineElement, PointElement, ArcElement,
    CategoryScale, LinearScale, Filler,
);

// ── Types ────────────────────────────────────────────────────────────────────

interface StatCard {
    current: number;
    change: number | null;
}

interface Props {
    period: string;
    meta: { period_label: string; date_range: string; generated_at: string };
    stats: {
        total_transactions: StatCard;
        revenue: StatCard;
        completed: StatCard;
        pending: StatCard;
        completion_rate: number;
        total_residents: number;
        status_breakdown: { pending: number; in_progress: number; completed: number; rejected: number };
    };
    trends: { labels: string[]; counts: number[]; revenues: number[] };
    documentTypes: { label: string; count: number }[];
    paymentMethods: { method: string; count: number; total: number }[];
    recentTransactions: {
        id: number; transaction_id: string; resident_name: string;
        document_type: string; status: string; payment_status: string;
        fee_amount: number; created_at: string;
    }[];
}

const props = defineProps<Props>();
const { staffDashboardBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = staffDashboardBreadcrumbs.value;

// ── Palette ───────────────────────────────────────────────────────────────────

const PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];

// ── Chart data ────────────────────────────────────────────────────────────────

const transactionBarData = computed(() => ({
    labels: props.trends.labels,
    datasets: [{
        label: 'Transactions',
        data: props.trends.counts,
        backgroundColor: 'rgba(59,130,246,0.75)',
        borderColor: '#3b82f6',
        borderRadius: 6,
        borderSkipped: false as const,
    }],
}));

const revenueLineData = computed(() => ({
    labels: props.trends.labels,
    datasets: [{
        label: 'Revenue (₱)',
        data: props.trends.revenues,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 3,
        pointBackgroundColor: '#10b981',
    }],
}));

const docTypeBarData = computed(() => ({
    labels: props.documentTypes.map(d => d.label),
    datasets: [{
        label: 'Requests',
        data: props.documentTypes.map(d => d.count),
        backgroundColor: PALETTE.slice(0, props.documentTypes.length),
        borderRadius: 4,
        borderSkipped: false as const,
    }],
}));

const paymentDoughnutData = computed(() => ({
    labels: props.paymentMethods.map(p => p.method),
    datasets: [{
        data: props.paymentMethods.map(p => p.count),
        backgroundColor: PALETTE,
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

// ── Chart options ─────────────────────────────────────────────────────────────

const sharedBarOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } },
    },
} as const;

const revenueLineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false } },
        y: {
            beginAtZero: true,
            ticks: {
                callback: (v: unknown) => `₱${((v as number) / 1000).toFixed(0)}k`,
            },
        },
    },
} as const;

const docTypeOpts = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } },
        y: { grid: { display: false } },
    },
} as const;

const doughnutOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right' as const, labels: { boxWidth: 12, padding: 10 } },
    },
} as const;

// ── Helpers ───────────────────────────────────────────────────────────────────

const formatPeso = (n: number) =>
    '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const changePeriod = (p: string) => router.get('/staff/dashboard', { period: p }, { preserveState: false });

const openReport = () => window.open(`/staff/dashboard/report?period=${props.period}`, '_blank');

const statusClass = (s: string) => ({
    pending:     'bg-amber-100 text-amber-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed:   'bg-green-100 text-green-800',
    rejected:    'bg-red-100 text-red-800',
}[s] ?? 'bg-gray-100 text-gray-800');

const paymentClass = (s: string) => ({
    paid:     'bg-green-100 text-green-800',
    pending:  'bg-amber-100 text-amber-800',
    failed:   'bg-red-100 text-red-800',
    refunded: 'bg-blue-100 text-blue-800',
}[s] ?? 'bg-gray-100 text-gray-800');

const formatStatus = (s: string) =>
    ({ in_progress: 'In Progress' }[s] ?? s.charAt(0).toUpperCase() + s.slice(1));
</script>

<template>
    <Head title="Analytics Dashboard" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gray-50 min-h-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 max-w-none">

                <!-- ── Header ──────────────────────────────────────────────── -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ meta.period_label }} &nbsp;·&nbsp; {{ meta.date_range }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Period selector -->
                        <div class="flex rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden text-sm font-medium">
                            <button
                                v-for="p in ['weekly','monthly','annually']"
                                :key="p"
                                @click="changePeriod(p)"
                                :class="[
                                    'px-4 py-2 capitalize transition-colors',
                                    period === p
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600 hover:bg-gray-50',
                                    p !== 'weekly' ? 'border-l border-gray-200' : '',
                                ]"
                            >{{ p }}</button>
                        </div>

                        <!-- Print report -->
                        <Button @click="openReport" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Printer class="h-4 w-4 mr-2" />
                            Print Report
                        </Button>
                    </div>
                </div>

                <!-- ── KPI Cards ───────────────────────────────────────────── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                    <!-- Total Transactions -->
                    <Card class="shadow-sm">
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <FileText class="h-5 w-5 text-blue-600" />
                                </div>
                                <span v-if="stats.total_transactions.change !== null" :class="[
                                    'inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-full',
                                    stats.total_transactions.change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                ]">
                                    <TrendingUp v-if="stats.total_transactions.change >= 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ Math.abs(stats.total_transactions.change) }}%
                                </span>
                                <span v-else class="text-xs text-gray-400 flex items-center gap-0.5">
                                    <Minus class="h-3 w-3" /> —
                                </span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.total_transactions.current }}</div>
                            <div class="text-sm text-gray-500 mt-1">Total Requests</div>
                        </CardContent>
                    </Card>

                    <!-- Revenue -->
                    <Card class="shadow-sm">
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-emerald-100 rounded-lg">
                                    <DollarSign class="h-5 w-5 text-emerald-600" />
                                </div>
                                <span v-if="stats.revenue.change !== null" :class="[
                                    'inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-full',
                                    stats.revenue.change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                ]">
                                    <TrendingUp v-if="stats.revenue.change >= 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ Math.abs(stats.revenue.change) }}%
                                </span>
                                <span v-else class="text-xs text-gray-400 flex items-center gap-0.5">
                                    <Minus class="h-3 w-3" /> —
                                </span>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 truncate">{{ formatPeso(stats.revenue.current) }}</div>
                            <div class="text-sm text-gray-500 mt-1">Revenue Collected</div>
                        </CardContent>
                    </Card>

                    <!-- Completion rate -->
                    <Card class="shadow-sm">
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-teal-100 rounded-lg">
                                    <CheckCircle class="h-5 w-5 text-teal-600" />
                                </div>
                                <span v-if="stats.completed.change !== null" :class="[
                                    'inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-full',
                                    stats.completed.change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                ]">
                                    <TrendingUp v-if="stats.completed.change >= 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ Math.abs(stats.completed.change) }}%
                                </span>
                                <span v-else class="text-xs text-gray-400 flex items-center gap-0.5">
                                    <Minus class="h-3 w-3" /> —
                                </span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.completion_rate }}%</div>
                            <div class="text-sm text-gray-500 mt-1">Completion Rate <span class="text-gray-400">({{ stats.completed.current }} done)</span></div>
                        </CardContent>
                    </Card>

                    <!-- Pending/Active -->
                    <Card class="shadow-sm">
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="p-2 bg-amber-100 rounded-lg">
                                    <Clock class="h-5 w-5 text-amber-600" />
                                </div>
                                <span v-if="stats.pending.change !== null" :class="[
                                    'inline-flex items-center gap-0.5 text-xs font-semibold px-2 py-0.5 rounded-full',
                                    stats.pending.change <= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                ]">
                                    <TrendingDown v-if="stats.pending.change <= 0" class="h-3 w-3" />
                                    <TrendingUp v-else class="h-3 w-3" />
                                    {{ Math.abs(stats.pending.change) }}%
                                </span>
                                <span v-else class="text-xs text-gray-400 flex items-center gap-0.5">
                                    <Minus class="h-3 w-3" /> —
                                </span>
                            </div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.pending.current }}</div>
                            <div class="text-sm text-gray-500 mt-1">Needs Attention</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Status Breakdown ────────────────────────────────────── -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                        <div class="w-2.5 h-2.5 rounded-full bg-amber-400 flex-shrink-0"></div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">{{ stats.status_breakdown.pending }}</div>
                            <div class="text-xs text-gray-500">Pending</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-400 flex-shrink-0"></div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">{{ stats.status_breakdown.in_progress }}</div>
                            <div class="text-xs text-gray-500">In Progress</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                        <div class="w-2.5 h-2.5 rounded-full bg-green-400 flex-shrink-0"></div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">{{ stats.status_breakdown.completed }}</div>
                            <div class="text-xs text-gray-500">Completed</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                        <div class="w-2.5 h-2.5 rounded-full bg-red-400 flex-shrink-0"></div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">{{ stats.status_breakdown.rejected }}</div>
                            <div class="text-xs text-gray-500">Rejected</div>
                        </div>
                    </div>
                </div>

                <!-- ── Charts row 1 ───────────────────────────────────────── -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">

                    <!-- Transaction volume bar -->
                    <Card class="lg:col-span-3 shadow-sm">
                        <CardHeader class="pb-1 pt-4 px-5">
                            <CardTitle class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                Transaction Volume
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="px-4 pb-4">
                            <div style="height: 260px">
                                <Bar :data="transactionBarData" :options="sharedBarOpts" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Document type distribution -->
                    <Card class="lg:col-span-2 shadow-sm">
                        <CardHeader class="pb-1 pt-4 px-5">
                            <CardTitle class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                By Document Type
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="px-4 pb-4">
                            <div v-if="documentTypes.length" style="height: 260px">
                                <Bar :data="docTypeBarData" :options="docTypeOpts" />
                            </div>
                            <div v-else class="flex h-64 items-center justify-center text-sm text-gray-400">
                                No requests in this period
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Charts row 2 ───────────────────────────────────────── -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-6">

                    <!-- Revenue trend line -->
                    <Card class="lg:col-span-3 shadow-sm">
                        <CardHeader class="pb-1 pt-4 px-5">
                            <CardTitle class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                Revenue Trend
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="px-4 pb-4">
                            <div style="height: 260px">
                                <LineChart :data="revenueLineData" :options="revenueLineOpts" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment methods doughnut -->
                    <Card class="lg:col-span-2 shadow-sm">
                        <CardHeader class="pb-1 pt-4 px-5">
                            <CardTitle class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                Payment Methods
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="px-4 pb-4">
                            <div v-if="paymentMethods.length" style="height: 260px">
                                <Doughnut :data="paymentDoughnutData" :options="doughnutOpts" />
                            </div>
                            <div v-else class="flex h-64 items-center justify-center text-sm text-gray-400">
                                No paid transactions in this period
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Recent Transactions ─────────────────────────────────── -->
                <Card class="shadow-sm">
                    <CardHeader class="pb-2 pt-4 px-5 flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            Recent Transactions
                        </CardTitle>
                        <Link href="/staff/transactions" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                            View all <ArrowRight class="h-3 w-3" />
                        </Link>
                    </CardHeader>
                    <CardContent class="px-0 pb-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/60">
                                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Transaction</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Resident</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Document</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fee</th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="t in recentTransactions"
                                        :key="t.id"
                                        class="hover:bg-gray-50/60 transition-colors"
                                    >
                                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ t.transaction_id }}</td>
                                        <td class="px-4 py-3 text-gray-900 max-w-[140px] truncate">{{ t.resident_name }}</td>
                                        <td class="px-4 py-3 text-gray-700 max-w-[160px] truncate">{{ t.document_type }}</td>
                                        <td class="px-4 py-3">
                                            <Badge :class="statusClass(t.status)" class="text-xs capitalize">
                                                {{ formatStatus(t.status) }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge :class="paymentClass(t.payment_status)" class="text-xs capitalize">
                                                {{ t.payment_status }}
                                            </Badge>
                                        </td>
                                        <td class="px-5 py-3 text-right tabular-nums text-gray-700">{{ formatPeso(t.fee_amount) }}</td>
                                        <td class="px-5 py-3 text-right text-gray-500 whitespace-nowrap">{{ t.created_at }}</td>
                                    </tr>
                                    <tr v-if="recentTransactions.length === 0">
                                        <td colspan="7" class="px-5 py-10 text-center text-gray-400 text-sm">
                                            No transactions found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>



            </div>
        </div>
    </StaffLayout>
</template>
