<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import resident from '@/routes/resident';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, FileText, Save } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { useFormHandlers } from '@/composables/useFormHandlers';

interface TransactionType {
    name: string;
    description: string;
    required_documents: string[];
    fee: number;
}

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description: string;
    status: string;
    fee_amount: number | string;
    fee_paid: boolean;
    submitted_at: string;
    created_at: string;
}

interface Props {
    transaction: Transaction;
    transactionTypes: Record<string, TransactionType>;
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
        title: `Edit Transaction ${props.transaction.transaction_id}`,
        href: resident.transactions.edit(props.transaction.id).url,
    },
];

const selectedType = ref(props.transaction.type);
const form = useForm({
    type: props.transaction.type,
    title: props.transaction.title,
    description: props.transaction.description,
    required_documents: [] as string[],
    fee_amount: Number(props.transaction.fee_amount),
});

const updateFormForType = (type: string) => {
    selectedType.value = type;
    const typeInfo = props.transactionTypes[type];
    if (typeInfo) {
        form.type = type;
        form.title = typeInfo.name;
        form.required_documents = typeInfo.required_documents;
        form.fee_amount = typeInfo.fee;
    }
};

// Use the composable for toast-enabled form submission
const { submitTransactionUpdate } = useFormHandlers();

const submit = () => {
    submitTransactionUpdate(form, resident.transactions.update(props.transaction.id).url, () => {
        // Redirect back to transactions index
        window.location.href = resident.transactions.index().url;
    });
};

const formatFeeAmount = (amount: number | string) => {
    const numAmount = Number(amount);
    return isNaN(numAmount) ? '0.00' : numAmount.toFixed(2);
};
</script>

<template>
    <Head :title="`Edit Transaction ${transaction.transaction_id}`" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-teal-50">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-6">
                        <Link
                            :href="resident.transactions.index().url"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-all duration-200"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Back to Transactions
                        </Link>
                    </div>

                    <div class="text-center space-y-4">
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-white rounded-2xl shadow-lg border border-gray-100">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <FileText class="h-6 w-6 text-blue-600" />
                            </div>
                            <div class="text-left">
                                <h1 class="text-2xl font-bold text-gray-900">Edit Transaction</h1>
                                <p class="text-gray-600">Update your transaction details</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="space-y-8">
                    <!-- Transaction Info Card -->
                    <Card class="shadow-xl border-0 bg-white/80 backdrop-blur-sm">
                        <CardHeader class="pb-6">
                            <CardTitle class="text-xl font-bold text-gray-900 flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <FileText class="h-4 w-4 text-blue-600" />
                                </div>
                                Transaction Details
                            </CardTitle>
                            <CardDescription class="text-gray-600">
                                Update the information for transaction {{ transaction.transaction_id }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Document Type Selection -->
                            <div class="space-y-3">
                                <Label class="text-sm font-semibold text-gray-700">Document Type</Label>
                                <Select :model-value="form.type" @update:model-value="updateFormForType">
                                    <SelectTrigger class="h-12 bg-gray-50 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <SelectValue placeholder="Select document type" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-white border border-gray-200 shadow-xl">
                                        <SelectItem
                                            v-for="(type, key) in transactionTypes"
                                            :key="key"
                                            :value="key"
                                            class="py-3 hover:bg-blue-50 focus:bg-blue-50"
                                        >
                                            <div class="flex items-center justify-between w-full">
                                                <span class="font-medium">{{ type.name }}</span>
                                                <span v-if="type.fee > 0" class="ml-3 text-sm font-bold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                                                    ₱{{ formatFeeAmount(type.fee) }}
                                                </span>
                                                <span v-else class="ml-3 text-sm font-bold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                                    Free
                                                </span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Title Input -->
                            <div class="space-y-3">
                                <Label class="text-sm font-semibold text-gray-700">Title</Label>
                                <Input
                                    v-model="form.title"
                                    placeholder="Enter transaction title"
                                    class="h-12 bg-gray-50 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="form.errors.title" class="text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <!-- Description Input -->
                            <div class="space-y-3">
                                <Label class="text-sm font-semibold text-gray-700">Description</Label>
                                <Textarea
                                    v-model="form.description"
                                    placeholder="Provide additional details about your request"
                                    rows="4"
                                    class="bg-gray-50 border-gray-200 focus:border-blue-500 focus:ring-blue-500 resize-none"
                                />
                                <div v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Required Documents -->
                            <div v-if="selectedType && transactionTypes[selectedType]?.required_documents?.length" class="space-y-4">
                                <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                                    <h3 class="text-sm font-semibold text-blue-900 mb-3">Required Documents</h3>
                                    <ul class="space-y-2">
                                        <li v-for="doc in transactionTypes[selectedType].required_documents" :key="doc" class="flex items-center gap-2 text-sm text-blue-800">
                                            <div class="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                                            {{ doc }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Fee Information -->
                            <div v-if="selectedType && transactionTypes[selectedType]?.fee > 0" class="space-y-4">
                                <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-green-900">Processing Fee</h3>
                                            <p class="text-xs text-green-700">This fee covers processing and documentation</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-green-600">Amount</p>
                                            <p class="text-lg font-black text-green-800">₱{{ formatFeeAmount(transactionTypes[selectedType].fee) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Free Service Notice -->
                            <div v-if="selectedType && transactionTypes[selectedType]?.fee === 0" class="space-y-4">
                                <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <span class="text-green-600 font-bold">✓</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-green-900">Free Service</h3>
                                            <p class="text-xs text-green-700">This document is provided at no cost</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between gap-4 pt-6">
                        <Link
                            :href="resident.transactions.index().url"
                            class="px-6 py-3 text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 transition-all duration-200 font-medium"
                        >
                            Cancel
                        </Link>

                        <Button
                            @click="submit"
                            :disabled="form.processing || !form.type || !form.title || !form.description"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-teal-600 hover:from-blue-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Updating...' : 'Update Transaction' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>
