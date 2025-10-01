<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import resident from '@/routes/resident';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Clock, CheckCircle, XCircle, AlertCircle, User, Calendar, DollarSign } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description: string;
    status: string;
    staff_notes: string;
    rejection_reason: string;
    fee_amount: number | string;
    fee_paid: boolean;
    submitted_at: string;
    processed_at: string;
    completed_at: string;
    staff?: {
        name: string;
    };
}

interface Props {
    transaction: Transaction;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'My Transactions',
        href: resident.transactions.index().url,
    },
    {
        title: `Transaction ${props.transaction.transaction_id}`,
        href: resident.transactions.show(props.transaction.id).url,
    },
];

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    completed: 'bg-green-100 text-green-800',
};

const statusIcons = {
    pending: Clock,
    in_progress: AlertCircle,
    approved: CheckCircle,
    rejected: XCircle,
    completed: CheckCircle,
};

const getStatusIcon = (status: string) => {
    return statusIcons[status as keyof typeof statusIcons] || Clock;
};

const getStatusColor = (status: string) => {
    return statusColors[status as keyof typeof statusColors] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatFeeAmount = (amount: number | string) => {
    const numAmount = Number(amount);
    return isNaN(numAmount) ? '0.00' : numAmount.toFixed(2);
};
</script>

<template>
    <Head :title="`Transaction ${transaction.transaction_id}`" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50/30">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <Link :href="resident.transactions.index().url">
                            <Button variant="outline" size="lg" class="shadow-sm">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Transactions
                            </Button>
                        </Link>
                        <div class="space-y-1">
                            <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">Transaction Details</h1>
                            <p class="text-lg text-gray-600">ID: {{ transaction.transaction_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card class="shadow-sm border-0 bg-white">
                            <CardHeader class="pb-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1">
                                        <CardTitle class="text-2xl font-bold text-gray-900">{{ transaction.title }}</CardTitle>
                                        <CardDescription class="text-base font-medium text-gray-600">{{ transaction.type.replace('_', ' ').toUpperCase() }}</CardDescription>
                                    </div>
                                    <Badge :class="getStatusColor(transaction.status)" class="w-fit text-sm px-3 py-1">
                                        <component :is="getStatusIcon(transaction.status)" class="h-3 w-3 mr-1" />
                                        {{ transaction.status.replace('_', ' ').toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div v-if="transaction.description" class="space-y-2">
                                    <h4 class="font-semibold text-gray-900">Description</h4>
                                    <p class="text-gray-700 leading-relaxed">{{ transaction.description }}</p>
                                </div>

                                <div v-if="transaction.staff_notes" class="space-y-2">
                                    <h4 class="font-semibold text-gray-900">Staff Notes</h4>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <p class="text-blue-800">{{ transaction.staff_notes }}</p>
                                    </div>
                                </div>

                                <div v-if="transaction.rejection_reason" class="space-y-2">
                                    <h4 class="font-semibold text-red-600">Rejection Reason</h4>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <p class="text-red-800">{{ transaction.rejection_reason }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <Card v-if="transaction.status === 'pending'" class="shadow-sm border-0 bg-white">
                            <CardHeader>
                                <CardTitle class="text-lg font-semibold text-gray-900">Actions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <Link :href="resident.transactions.edit(transaction.id).url" class="flex-1">
                                        <Button class="w-full" size="lg">
                                            Edit Request
                                        </Button>
                                    </Link>
                                    <Link :href="resident.transactions.destroy(transaction.id).url" method="delete" class="flex-1">
                                        <Button variant="destructive" class="w-full" size="lg">
                                            Cancel Request
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Status Timeline -->
                        <Card class="shadow-sm border-0 bg-white">
                            <CardHeader>
                                <CardTitle class="text-lg font-semibold text-gray-900">Status Timeline</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900">Submitted</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.submitted_at) }}</p>
                                    </div>
                                </div>

                                <div v-if="transaction.processed_at" class="flex items-start gap-4">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900">Processing Started</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.processed_at) }}</p>
                                    </div>
                                </div>

                                <div v-if="transaction.completed_at" class="flex items-start gap-4">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mt-1 flex-shrink-0"></div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900">Completed</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.completed_at) }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Assignment Info -->
                        <Card v-if="transaction.staff" class="shadow-sm border-0 bg-white">
                            <CardHeader>
                                <CardTitle class="text-lg font-semibold text-gray-900">Assigned Staff</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <User class="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-semibold text-gray-900">{{ transaction.staff.name }}</p>
                                        <p class="text-sm text-gray-500">Processing your request</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Fee Information -->
                        <Card v-if="transaction.fee_amount > 0" class="shadow-sm border-0 bg-white">
                            <CardHeader>
                                <CardTitle class="text-lg font-semibold text-gray-900">Fee Information</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm font-medium text-gray-600">Processing Fee</span>
                                    <span class="text-lg font-bold text-gray-900">₱{{ formatFeeAmount(transaction.fee_amount) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm font-medium text-gray-600">Payment Status</span>
                                    <Badge :class="transaction.fee_paid ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200'">
                                        {{ transaction.fee_paid ? 'Paid' : 'Pending' }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Quick Info -->
                        <Card class="shadow-sm border-0 bg-white">
                            <CardHeader>
                                <CardTitle class="text-lg font-semibold text-gray-900">Quick Info</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                        <Calendar class="h-5 w-5 text-gray-600" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900">Submitted</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.submitted_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="transaction.fee_amount > 0" class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                        <DollarSign class="h-5 w-5 text-gray-600" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-gray-900">Fee</p>
                                        <p class="text-xs text-gray-500">₱{{ formatFeeAmount(transaction.fee_amount) }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>
