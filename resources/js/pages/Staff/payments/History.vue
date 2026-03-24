<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFormHandlers } from '@/composables/useFormHandlers';
import { useUtils } from '@/composables/useUtils';
import staff from '@/routes/staff';
import {
    Banknote,
    CreditCard,
    Eye,
    Filter,
    Receipt,
    Search,
    TrendingUp,
    Wallet,
    ArrowDownRight,
    AlertCircle,
} from 'lucide-vue-next';

interface Resident {
    id: number;
    name: string;
    email: string;
}

interface DocumentType {
    id: number;
    name: string;
    code: string;
}

interface PaymentVerifier {
    id: number;
    name: string;
}

interface PaymentRow {
    id: number;
    transaction_id: string;
    title: string;
    fee_amount: string | number;
    payment_status: string;
    payment_method: string | null;
    amount_paid: string | number | null;
    payment_reference: string | null;
    receipt_number: string | null;
    payment_date: string | null;
    payment_verified_at: string | null;
    created_at: string;
    resident: Resident;
    document_type: DocumentType | null;
    payment_verifier: PaymentVerifier | null;
}

interface Summary {
    matching_count: number;
    paid_count: number;
    pending_count: number;
    failed_count: number;
    refunded_count: number;
    collected_total: string | number;
    outstanding_fees_total: string | number;
}

interface Props {
    payments: {
        data: PaymentRow[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: Summary;
    filters: {
        search: string;
        payment_status: string;
        payment_method: string;
        date_from: string | null;
        date_to: string | null;
        sort: string;
        direction: string;
        per_page: number;
    };
}

const props = defineProps<Props>();

const { staffPaymentHistoryBreadcrumbs } = useBreadcrumbs();
const { formatPeso, formatDateTime } = useUtils();

const breadcrumbs = staffPaymentHistoryBreadcrumbs.value;

const searchQuery = ref(props.filters.search || '');

watch(
    () => props.filters.search,
    (s) => {
        searchQuery.value = s || '';
    }
);

const filterForm = useForm({
    search: props.filters.search || '',
    payment_status: props.filters.payment_status || 'all',
    payment_method: props.filters.payment_method || 'all',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort: props.filters.sort || 'recorded_at',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 20,
    page: 1,
});

let searchDebounce: ReturnType<typeof setTimeout> | undefined;
watch(searchQuery, () => {
    if (searchDebounce !== undefined) {
        clearTimeout(searchDebounce);
    }
    searchDebounce = setTimeout(() => {
        filterForm.search = searchQuery.value;
        filterForm.page = 1;
        filterForm.get(staff.payments.history.url(), { preserveState: true, preserveScroll: true });
    }, 350);
});

const applyFilters = () => {
    filterForm.search = searchQuery.value;
    filterForm.page = 1;
    filterForm.get(staff.payments.history.url(), { preserveState: true, preserveScroll: true });
};

const clearFilters = () => {
    searchQuery.value = '';
    filterForm.search = '';
    filterForm.payment_status = 'all';
    filterForm.payment_method = 'all';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.sort = 'recorded_at';
    filterForm.direction = 'desc';
    filterForm.per_page = 20;
    filterForm.page = 1;
    filterForm.get(staff.payments.history.url(), { preserveState: true, preserveScroll: true });
};

const goToPage = (page: number) => {
    filterForm.page = page;
    filterForm.search = searchQuery.value;
    filterForm.get(staff.payments.history.url(), { preserveState: true, preserveScroll: true });
};

const getPaymentStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-900 border-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-900 border-amber-200';
        case 'failed':
            return 'bg-red-100 text-red-900 border-red-200';
        case 'refunded':
            return 'bg-sky-100 text-sky-900 border-sky-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getPaymentMethodLabel = (method: string | null) => {
    if (!method) {
        return '—';
    }
    const map: Record<string, string> = {
        cash: 'Cash',
        gcash: 'GCash',
        paymaya: 'PayMaya',
        bank_transfer: 'Bank Transfer',
        check: 'Check',
    };
    return map[method] ?? method;
};

const recordedAt = (row: PaymentRow) => row.payment_date ?? row.created_at;

const documentLabel = (row: PaymentRow) => row.document_type?.name ?? row.title;
</script>

<template>
    <Head title="Payment History" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 shadow-xl mb-6 rounded-2xl">
                    <div class="absolute inset-0 bg-black/10" />
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full translate-x-24 -translate-y-24" />
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-20 translate-y-20" />
                    </div>
                    <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="text-white">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                        <Receipt class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Payment History</h1>
                                        <p class="text-blue-100 text-base sm:text-lg mt-1 max-w-2xl">
                                            Trace every fee-bearing request: receipts, amounts, methods, and verification—aligned with your transaction records.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-blue-100">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-emerald-300" />
                                        {{ summary.paid_count }} paid
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-amber-300" />
                                        {{ summary.pending_count }} pending
                                    </span>
                                    <Link
                                        :href="staff.payments.statistics.url()"
                                        class="inline-flex items-center gap-1.5 underline-offset-4 hover:underline text-white/95"
                                    >
                                        View payment statistics
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <Card class="shadow-md border-gray-200/80 bg-white/90 backdrop-blur-sm">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-sm font-medium text-gray-600">Matching records</CardTitle>
                            <Wallet class="h-4 w-4 text-indigo-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-gray-900">{{ summary.matching_count }}</p>
                            <p class="text-xs text-gray-500 mt-1">After current filters</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200/80 bg-white/90 backdrop-blur-sm">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-sm font-medium text-gray-600">Collected (paid)</CardTitle>
                            <TrendingUp class="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ formatPeso(summary.collected_total) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Sum of amount paid</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200/80 bg-white/90 backdrop-blur-sm">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-sm font-medium text-gray-600">Outstanding fees</CardTitle>
                            <ArrowDownRight class="h-4 w-4 text-amber-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ formatPeso(summary.outstanding_fees_total) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Pending &amp; failed (fee due)</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-md border-gray-200/80 bg-white/90 backdrop-blur-sm">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-sm font-medium text-gray-600">Exceptions</CardTitle>
                            <AlertCircle class="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ summary.failed_count + summary.refunded_count }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ summary.failed_count }} failed · {{ summary.refunded_count }} refunded
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="mb-6">
                    <Card class="shadow-lg border-gray-200">
                        <CardHeader class="pb-4">
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Filter class="h-5 w-5" />
                                Filters &amp; sort
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                                <div class="space-y-2 lg:col-span-2">
                                    <label class="text-sm font-medium text-gray-700">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            v-model="searchQuery"
                                            placeholder="Receipt, reference, transaction ID, resident…"
                                            class="pl-10 h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Payment status</label>
                                    <Select v-model="filterForm.payment_status" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All statuses</SelectItem>
                                            <SelectItem value="paid">Paid</SelectItem>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="failed">Failed</SelectItem>
                                            <SelectItem value="refunded">Refunded</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Method</label>
                                    <Select v-model="filterForm.payment_method" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All methods</SelectItem>
                                            <SelectItem value="cash">Cash</SelectItem>
                                            <SelectItem value="gcash">GCash</SelectItem>
                                            <SelectItem value="paymaya">PayMaya</SelectItem>
                                            <SelectItem value="bank_transfer">Bank transfer</SelectItem>
                                            <SelectItem value="check">Check</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">From</label>
                                    <Input v-model="filterForm.date_from" type="date" class="h-10 border-gray-200" @change="applyFilters" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">To</label>
                                    <Input v-model="filterForm.date_to" type="date" class="h-10 border-gray-200" @change="applyFilters" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Sort by</label>
                                    <Select v-model="filterForm.sort" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="recorded_at">Recorded date</SelectItem>
                                            <SelectItem value="amount_paid">Amount paid</SelectItem>
                                            <SelectItem value="fee_amount">Fee amount</SelectItem>
                                            <SelectItem value="receipt_number">Receipt number</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Direction</label>
                                    <Select v-model="filterForm.direction" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="desc">Newest first</SelectItem>
                                            <SelectItem value="asc">Oldest first</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Rows per page</label>
                                    <Select
                                        :model-value="String(filterForm.per_page)"
                                        @update:model-value="
                                            (v) => {
                                                filterForm.per_page = Number(v);
                                                applyFilters();
                                            }
                                        "
                                    >
                                        <SelectTrigger class="h-10 border-gray-200">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="15">15</SelectItem>
                                            <SelectItem value="20">20</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Button type="button" variant="default" class="h-10" :disabled="filterForm.processing" @click="applyFilters">
                                    Apply
                                </Button>
                                <Button type="button" variant="outline" class="h-10" @click="clearFilters">Reset</Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="shadow-lg border-gray-200 overflow-hidden">
                    <CardHeader class="border-b border-gray-100 bg-gray-50/80 px-5 py-4 sm:px-6 sm:py-5">
                        <CardTitle class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-base sm:text-lg">
                            <span class="flex items-center gap-3">
                                <Banknote class="h-5 w-5 shrink-0 text-indigo-600" />
                                Ledger ({{ payments.total }})
                            </span>
                            <span class="text-sm font-normal text-gray-500 leading-relaxed max-w-xl">
                                Each row links to the source transaction for full context.
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto px-4 py-5 sm:px-6 sm:py-6">
                            <Table class="w-full min-w-[72rem]">
                                <TableHeader>
                                    <TableRow class="bg-gray-50/90 hover:bg-gray-50/90 border-b border-gray-200">
                                        <TableHead class="h-auto min-h-12 py-3.5 pl-1 pr-4 text-left font-semibold text-gray-900 whitespace-nowrap sm:pl-2">
                                            Receipt
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-nowrap">
                                            Transaction
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-normal min-w-[11rem] max-w-[15rem]">
                                            Resident
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-normal min-w-[9rem]">
                                            Document
                                        </TableHead>
                                        <TableHead
                                            class="h-auto min-h-12 pl-6 pr-4 py-3.5 text-right font-semibold text-gray-900 whitespace-nowrap tabular-nums"
                                        >
                                            Fee
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 text-right font-semibold text-gray-900 whitespace-nowrap tabular-nums">
                                            Paid
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-nowrap min-w-[6.5rem]">
                                            Method
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-nowrap">
                                            Status
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-nowrap">
                                            Recorded
                                        </TableHead>
                                        <TableHead class="h-auto min-h-12 px-4 py-3.5 font-semibold text-gray-900 whitespace-nowrap min-w-[7rem]">
                                            Verified by
                                        </TableHead>
                                        <TableHead
                                            class="h-auto min-h-12 py-3.5 pl-4 pr-1 text-right font-semibold text-gray-900 whitespace-nowrap sm:pr-2"
                                        >
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="row in payments.data"
                                        :key="row.id"
                                        class="border-b border-gray-100 hover:bg-gray-50/80 transition-colors"
                                    >
                                        <TableCell class="py-4 pl-1 pr-4 align-middle font-mono text-sm whitespace-nowrap sm:pl-2">
                                            {{ row.receipt_number ?? '—' }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle font-mono text-sm whitespace-nowrap">
                                            {{ row.transaction_id }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-top whitespace-normal min-w-[11rem] max-w-[15rem]">
                                            <div class="flex flex-col gap-1.5">
                                                <div class="font-medium leading-snug text-gray-900">{{ row.resident.name }}</div>
                                                <div class="text-xs leading-normal text-gray-500 break-all">{{ row.resident.email }}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle text-sm leading-relaxed text-gray-800 whitespace-normal">
                                            {{ documentLabel(row) }}
                                        </TableCell>
                                        <TableCell class="pl-6 pr-4 py-4 align-middle text-right text-sm tabular-nums font-medium whitespace-nowrap">
                                            {{ formatPeso(row.fee_amount) }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle text-right text-sm tabular-nums whitespace-nowrap">
                                            {{ row.amount_paid != null ? formatPeso(row.amount_paid) : '—' }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle text-sm whitespace-nowrap">
                                            {{ getPaymentMethodLabel(row.payment_method) }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle whitespace-normal">
                                            <Badge variant="outline" :class="getPaymentStatusBadgeClass(row.payment_status)" class="capitalize">
                                                {{ row.payment_status }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle text-sm text-gray-600 whitespace-nowrap">
                                            {{ formatDateTime(recordedAt(row)) }}
                                        </TableCell>
                                        <TableCell class="px-4 py-4 align-middle text-sm text-gray-700 whitespace-nowrap">
                                            {{ row.payment_verifier?.name ?? '—' }}
                                        </TableCell>
                                        <TableCell class="py-4 pl-4 pr-1 text-right align-middle sm:pr-2">
                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                <Link :href="staff.transactions.show.url(row.id)">
                                                    <Button variant="ghost" size="sm" class="h-9 gap-1.5 px-3">
                                                        <Eye class="h-4 w-4" />
                                                        <span class="hidden sm:inline">Transaction</span>
                                                    </Button>
                                                </Link>
                                                <Link
                                                    v-if="row.payment_status === 'pending'"
                                                    :href="staff.payments.show.url(row.id)"
                                                >
                                                    <Button variant="outline" size="sm" class="h-9 gap-1.5 px-3">
                                                        <CreditCard class="h-3.5 w-3.5" />
                                                        <span class="hidden sm:inline">Pay</span>
                                                    </Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="payments.data.length === 0">
                                        <TableCell colspan="11" class="px-6 py-16 text-center text-gray-500">
                                            No payment records match your filters. Try clearing search or widening the date range.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div
                            v-if="payments.last_page > 1"
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-4 sm:px-6 sm:py-5"
                        >
                            <div class="text-sm text-gray-600">
                                <template v-if="payments.from != null && payments.to != null">
                                    Showing {{ payments.from }}–{{ payments.to }} of {{ payments.total }}
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="payments.current_page > 1"
                                    variant="outline"
                                    size="sm"
                                    :disabled="filterForm.processing"
                                    @click="goToPage(payments.current_page - 1)"
                                >
                                    Previous
                                </Button>
                                <span class="text-sm text-gray-600 tabular-nums">
                                    Page {{ payments.current_page }} / {{ payments.last_page }}
                                </span>
                                <Button
                                    v-if="payments.current_page < payments.last_page"
                                    variant="outline"
                                    size="sm"
                                    :disabled="filterForm.processing"
                                    @click="goToPage(payments.current_page + 1)"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StaffLayout>
</template>
