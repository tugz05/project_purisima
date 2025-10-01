<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Eye, Filter, Clock, CheckCircle, XCircle, AlertCircle, UserPlus } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFormHandlers } from '@/composables/useFormHandlers';
import { useUtils } from '@/composables/useUtils';
import { toast } from 'vue-sonner';

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    status: string;
    fee_amount: number;
    fee_paid: boolean;
    submitted_at: string;
    created_at: string;
    resident: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
    };
}

const props = defineProps<Props>();

// Composables
const { staffTransactionsBreadcrumbs } = useBreadcrumbs();
const { createFilterForm, createDebouncedSearch } = useFormHandlers();
const { formatPeso, formatDateShort } = useUtils();

// State
const searchQuery = ref(props.filters.search || '');
const filterForm = createFilterForm(props.filters);

// Breadcrumbs
const breadcrumbs = staffTransactionsBreadcrumbs.value;

// Debounced search
createDebouncedSearch(searchQuery, filterForm, '/staff/transactions');

// Methods
const applyFilters = () => {
    filterForm.get('/staff/transactions');
};

const clearSearch = () => {
    searchQuery.value = '';
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.type = 'all';
    filterForm.get('/staff/transactions');
};

const assignToMe = (transactionId: number) => {
    // Find the transaction to check its status
    const transaction = props.transactions.data.find(t => t.id === transactionId);

    // Check if transaction is pending before attempting assignment
    if (!transaction || transaction.status !== 'pending') {
        toast.error('Cannot assign transaction', {
            description: transaction ?
                `Transaction status is ${transaction.status.replace('_', ' ')}, only pending transactions can be assigned.` :
                'Transaction not found.'
        });
        return;
    }

    const assignForm = useForm({});
    assignForm.post(`/staff/transactions/${transactionId}/assign`, {
        onSuccess: () => {
            toast.success('Transaction assigned successfully!', {
                description: 'You have been assigned to this transaction.',
                action: {
                    label: 'View',
                    onClick: () => window.location.href = `/staff/transactions/${transactionId}`
                }
            });
            // Refresh the page to show updated assignment
            window.location.reload();
        },
        onError: (errors) => {
            toast.error('Failed to assign transaction', {
                description: errors.message || 'Please try again later.'
            });
        }
    });
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending':
            return Clock;
        case 'in_progress':
            return AlertCircle;
        case 'completed':
            return CheckCircle;
        case 'rejected':
            return XCircle;
        default:
            return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Transactions" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header Section -->
                <div class="mb-6 md:mb-8">
                    <div class="space-y-2">
                        <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                            Transactions
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 font-medium">Manage document requests and processing</p>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="mb-6">
                    <Card class="shadow-lg border-gray-200">
                        <CardHeader class="pb-4">
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Filter class="h-5 w-5" />
                                Filters
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Search -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            v-model="searchQuery"
                                            placeholder="Search transactions..."
                                            class="pl-10 h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Status</label>
                                    <Select v-model="filterForm.status" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                            <SelectValue placeholder="All Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="in_progress">In Progress</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Type Filter -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Document Type</label>
                                    <Select v-model="filterForm.type" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                            <SelectValue placeholder="All Types" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Types</SelectItem>
                                            <SelectItem value="barangay_clearance">Barangay Clearance</SelectItem>
                                            <SelectItem value="residency_certificate">Residency Certificate</SelectItem>
                                            <SelectItem value="business_permit">Business Permit</SelectItem>
                                            <SelectItem value="indigency_certificate">Indigency Certificate</SelectItem>
                                            <SelectItem value="cedula">Cedula</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Clear Filters -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">&nbsp;</label>
                                    <Button @click="clearSearch" variant="outline" class="w-full h-10 border-gray-200 hover:bg-gray-50">
                                        Clear Filters
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Transactions Table -->
                <Card class="shadow-lg border-gray-200">
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>Transactions ({{ props.transactions.total }})</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-gray-50">
                                        <TableHead class="font-semibold text-gray-900">Transaction ID</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Resident</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Document Type</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Fee</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Status</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Submitted</TableHead>
                                        <TableHead class="font-semibold text-gray-900 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="transaction in props.transactions.data" :key="transaction.id" class="hover:bg-gray-50">
                                        <TableCell class="py-4 px-4">
                                            <div class="font-mono text-sm font-medium text-gray-900">
                                                {{ transaction.transaction_id }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ transaction.resident.name }}</div>
                                                <div class="text-sm text-gray-500">{{ transaction.resident.email }}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <div class="font-medium text-gray-900">{{ transaction.title }}</div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900">{{ formatPeso(transaction.fee_amount) }}</span>
                                                <Badge v-if="transaction.fee_paid" variant="secondary" class="text-xs">
                                                    Paid
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <Badge :class="getStatusColor(transaction.status)">
                                                {{ transaction.status.replace('_', ' ').toUpperCase() }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <span class="text-sm text-gray-600">{{ formatDateShort(transaction.submitted_at) }}</span>
                                        </TableCell>
                                        <TableCell class="py-4 px-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button
                                                    v-if="transaction.status === 'pending'"
                                                    @click="assignToMe(transaction.id)"
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-8 px-3 text-xs font-medium bg-green-50 hover:bg-green-100 text-green-700 border-green-200 hover:border-green-300"
                                                >
                                                    <UserPlus class="h-3 w-3 mr-1" />
                                                    Assign
                                                </Button>
                                                <Link :href="`/staff/transactions/${transaction.id}`">
                                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 hover:bg-blue-50">
                                                        <Eye class="h-4 w-4 text-blue-600" />
                                                    </Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="props.transactions.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                            <div class="text-sm text-gray-700">
                                Showing {{ props.transactions.from }} to {{ props.transactions.to }} of {{ props.transactions.total }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="props.transactions.current_page > 1"
                                    @click="() => { filterForm.page = props.transactions.current_page - 1; applyFilters(); }"
                                    variant="outline"
                                    size="sm"
                                >
                                    Previous
                                </Button>
                                <span class="text-sm text-gray-600">
                                    Page {{ props.transactions.current_page }} of {{ props.transactions.last_page }}
                                </span>
                                <Button
                                    v-if="props.transactions.current_page < props.transactions.last_page"
                                    @click="() => { filterForm.page = props.transactions.current_page + 1; applyFilters(); }"
                                    variant="outline"
                                    size="sm"
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
