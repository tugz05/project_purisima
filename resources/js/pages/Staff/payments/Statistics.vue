<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useUtils } from '@/composables/useUtils';
import staff from '@/routes/staff';
import { BarChart3, PieChart, Receipt } from 'lucide-vue-next';

interface Statistics {
    total_transactions: number;
    paid_transactions: number;
    pending_payments: number;
    failed_payments: number;
    refunded_payments: number;
    total_revenue: string | number;
    pending_revenue: string | number;
    payment_completion_rate: number;
}

interface MethodStat {
    count: number;
    total_amount: string | number;
    percentage: number;
}

interface Props {
    statistics: Statistics;
    paymentMethodStats: Record<string, MethodStat>;
}

const props = defineProps<Props>();

const { staffPaymentStatisticsBreadcrumbs } = useBreadcrumbs();
const { formatPeso } = useUtils();

const breadcrumbs = staffPaymentStatisticsBreadcrumbs.value;

const methodRows = computed(() => {
    const entries = Object.entries(props.paymentMethodStats || {});
    const paidTotal = Number(props.statistics.paid_transactions) || 0;

    return entries.map(([method, stat]) => ({
        method,
        label: methodLabel(method),
        count: stat.count,
        total_amount: stat.total_amount,
        share: paidTotal > 0 ? Math.round((stat.count / paidTotal) * 1000) / 10 : 0,
    }));
});

const methodLabel = (method: string) => {
    const map: Record<string, string> = {
        cash: 'Cash',
        gcash: 'GCash',
        paymaya: 'PayMaya',
        bank_transfer: 'Bank Transfer',
        check: 'Check',
    };
    return map[method] ?? method;
};
</script>

<template>
    <Head title="Payment Statistics" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 shadow-xl mb-6 rounded-2xl">
                    <div class="absolute inset-0 bg-black/10" />
                    <div class="relative px-4 sm:px-6 lg:px-8 py-8 text-white">
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                                        <BarChart3 class="h-7 w-7" />
                                    </div>
                                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Payment statistics</h1>
                                </div>
                                <p class="text-blue-100 max-w-2xl">
                                    High-level view of collection performance across all document transactions.
                                </p>
                            </div>
                            <Link
                                :href="staff.payments.history.url()"
                                class="inline-flex items-center justify-center rounded-lg bg-white/15 hover:bg-white/25 px-4 py-2.5 text-sm font-medium backdrop-blur-sm transition-colors"
                            >
                                <Receipt class="h-4 w-4 mr-2" />
                                Payment history
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Card class="shadow-md border-gray-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600">Total revenue (paid)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold tabular-nums text-gray-900">{{ formatPeso(statistics.total_revenue) }}</p>
                            <CardDescription class="mt-1">{{ statistics.paid_transactions }} completed payments</CardDescription>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600">Pending collection</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold tabular-nums text-amber-700">{{ formatPeso(statistics.pending_revenue) }}</p>
                            <CardDescription class="mt-1">{{ statistics.pending_payments }} awaiting payment</CardDescription>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600">Completion rate</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold tabular-nums text-indigo-700">{{ statistics.payment_completion_rate }}%</p>
                            <CardDescription class="mt-1">Paid vs. all transactions</CardDescription>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600">Attention needed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ statistics.failed_payments + statistics.refunded_payments }}
                            </p>
                            <CardDescription class="mt-1">
                                {{ statistics.failed_payments }} failed · {{ statistics.refunded_payments }} refunded
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>

                <Card class="shadow-lg border-gray-200">
                    <CardHeader class="border-b border-gray-100 bg-gray-50/80">
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <PieChart class="h-5 w-5 text-indigo-600" />
                            Paid volume by method
                        </CardTitle>
                        <CardDescription>Breakdown of successful payments by channel</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-gray-50/90">
                                        <TableHead class="font-semibold">Method</TableHead>
                                        <TableHead class="font-semibold text-right">Transactions</TableHead>
                                        <TableHead class="font-semibold text-right">Share</TableHead>
                                        <TableHead class="font-semibold text-right">Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="row in methodRows" :key="row.method" class="hover:bg-gray-50/80">
                                        <TableCell class="font-medium">{{ row.label }}</TableCell>
                                        <TableCell class="text-right tabular-nums">{{ row.count }}</TableCell>
                                        <TableCell class="text-right tabular-nums text-gray-600">{{ row.share }}%</TableCell>
                                        <TableCell class="text-right tabular-nums font-medium">{{ formatPeso(row.total_amount) }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="methodRows.length === 0">
                                        <TableCell colspan="4" class="py-12 text-center text-gray-500">
                                            No paid transactions yet to analyze by method.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StaffLayout>
</template>
