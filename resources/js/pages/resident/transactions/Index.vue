<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch, nextTick, ref } from 'vue';
import resident from '@/routes/resident';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus, FileText, Clock, Eye, Edit, ArrowLeft, CheckCircle, XCircle, AlertCircle, User, Calendar, DollarSign, Upload, X } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useTransactions } from '@/composables/useTransactions';
import { useFormHandlers } from '@/composables/useFormHandlers';
import { useUtils } from '@/composables/useUtils';

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description: string;
    status: string;
    staff_notes?: string;
    rejection_reason?: string;
    fee_amount: number | string;
    fee_paid: boolean;
    submitted_at: string;
    processed_at?: string;
    completed_at?: string;
    created_at: string;
    staff?: {
        name: string;
    };
}

interface TransactionType {
    name: string;
    fee: number;
    required_documents: string[];
}

interface Props {
    transactions: Transaction[];
    filters: {
        status?: string;
        type?: string;
    };
    transactionTypes: Record<string, TransactionType>;
}

const props = defineProps<Props>();

// Composables
const { residentTransactionsBreadcrumbs } = useBreadcrumbs();
const {
    selectedType,
    sheetOpen,
    getStatusColor,
    getStatusIcon,
    getTypeName,
    formatFeeAmount
} = useTransactions();
const {
    createTransactionForm,
    updateFormForTransactionType,
    submitTransactionCreate,
    submitTransactionUpdate,
    createFilterForm,
    applyFilters: applyFiltersUtil,
    addSubmittedDocument,
    removeSubmittedDocument,
    addMultipleSubmittedDocuments
} = useFormHandlers();
const { formatDateShort } = useUtils();

// Forms
const createForm = createTransactionForm();
const filterForm = createFilterForm(props.filters);

// Sheet states
const viewSheetOpen = ref(false);
const editSheetOpen = ref(false);
const selectedTransaction = ref<Transaction | null>(null);
const editForm = ref<any>(null);

// Breadcrumbs
const breadcrumbs = residentTransactionsBreadcrumbs.value;

// Methods
const updateFormForType = (type: string) => {
    if (type && typeof type === 'string' && props.transactionTypes && props.transactionTypes[type]) {
        try {
            updateFormForTransactionType(type, createForm, props.transactionTypes);
        } catch (error) {
            console.warn('Error updating form for type:', type, error);
        }
    }
};

const submitCreate = () => {
    submitTransactionCreate(createForm, resident.transactions.store().url, () => {
        sheetOpen.value = false;
        selectedType.value = '';
    });
};

const applyFilters = () => {
    applyFiltersUtil(filterForm, resident.transactions.index().url);
};

// View and Edit methods
const openViewSheet = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    viewSheetOpen.value = true;
};

const openEditSheet = (transaction: Transaction) => {
    selectedTransaction.value = transaction;
    editForm.value = useForm({
        type: transaction.type,
        title: transaction.title,
        description: transaction.description,
        required_documents: [] as string[],
        fee_amount: Number(transaction.fee_amount),
    });
    editSheetOpen.value = true;
};

const submitEdit = () => {
    if (editForm.value && selectedTransaction.value) {
        submitTransactionUpdate(editForm.value, resident.transactions.update(selectedTransaction.value.id).url, () => {
            editSheetOpen.value = false;
            selectedTransaction.value = null;
            editForm.value = null;
        });
    }
};

// Watch for sheet close to clean up selectedType
watch(sheetOpen, (newValue) => {
    if (!newValue) {
        // Use nextTick to ensure DOM updates are complete before resetting
        nextTick(() => {
            selectedType.value = '';
            createForm.reset();
        });
    }
});
</script>

<template>
    <Head title="My Transactions" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header Section -->
                <div class="mb-6 md:mb-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 md:gap-6">
                        <div class="space-y-2">
                            <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                                My Transactions
                            </h1>
                            <p class="text-lg md:text-xl text-gray-600 font-medium">Manage your barangay document requests efficiently</p>
                        </div>
                        <Sheet v-model:open="sheetOpen">
                            <SheetTrigger as-child>
                                <Button size="lg" class="shadow-xl hover:shadow-2xl transition-all duration-300 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white border-0 px-4 md:px-8 py-3 md:py-4 rounded-xl font-semibold text-sm md:text-base">
                                    <Plus class="h-6 w-6 mr-3" />
                                    New Request
                                </Button>
                            </SheetTrigger>
                            <SheetContent class="w-full sm:w-[580px] lg:w-[640px] p-0 flex flex-col h-full">
                                <div class="p-6 sm:p-8 flex-shrink-0">
                                    <SheetHeader class="pb-6">
                                        <SheetTitle class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">New Document Request</SheetTitle>
                                        <SheetDescription class="text-base sm:text-lg text-gray-600 leading-relaxed">
                                            Select the type of document you need and provide the required information
                                        </SheetDescription>
                                    </SheetHeader>
                                </div>

                                <div class="flex-1 overflow-y-auto px-6 sm:px-8">
                                    <form @submit.prevent="submitCreate" enctype="multipart/form-data" class="space-y-6 sm:space-y-8">
                                        <div class="space-y-3">
                                            <Label for="type" class="text-base font-bold text-gray-800">Document Type</Label>
                                            <Select
                                                :key="`select-${sheetOpen}`"
                                                v-model="selectedType"
                                                @update:model-value="(value) => {
                                                    if (value && typeof value === 'string') {
                                                        updateFormForType(value);
                                                    }
                                                }"
                                            >
                                                <SelectTrigger class="h-12 sm:h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                                                    <SelectValue placeholder="Select document type" />
                                                </SelectTrigger>
                                                <SelectContent class="rounded-xl border-2 border-gray-200 shadow-xl">
                                                    <template v-for="(type, key) in transactionTypes" :key="key">
                                                        <SelectItem
                                                            v-if="type && type.name && typeof type.fee !== 'undefined'"
                                                            :value="key"
                                                            class="py-4 px-4 hover:bg-blue-50 transition-colors duration-200"
                                                        >
                                                            <div class="flex items-center justify-between w-full">
                                                                <span class="font-medium">{{ type.name }}</span>
                                                                <span class="ml-3 text-sm font-bold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">₱{{ Number(type.fee).toFixed(2) }}</span>
                                                            </div>
                                                        </SelectItem>
                                                    </template>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div v-if="selectedType" class="space-y-3">
                                            <Label for="title" class="text-base font-bold text-gray-800">Title</Label>
                                            <Input
                                                id="title"
                                                v-model="createForm.title"
                                                placeholder="Enter document title"
                                                class="h-12 sm:h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-base"
                                                required
                                            />
                                        </div>

                                        <div v-if="selectedType" class="space-y-3">
                                            <Label for="description" class="text-base font-bold text-gray-800">Description</Label>
                                            <Textarea
                                                id="description"
                                                v-model="createForm.description"
                                                placeholder="Additional details or special requirements"
                                                rows="5"
                                                class="resize-none border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-base"
                                            />
                                        </div>

                                        <div v-if="selectedType" class="space-y-4">
                                            <Label class="text-base font-bold text-gray-800">Required Documents</Label>

                                            <!-- Individual Document Upload Sections -->
                                            <div v-if="transactionTypes[selectedType]?.required_documents?.length" class="space-y-4">
                                                <div
                                                    v-for="doc in transactionTypes[selectedType].required_documents"
                                                    :key="doc"
                                                    class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6 space-y-4"
                                                >
                                                    <!-- Document Type Header -->
                                                    <div class="flex items-center gap-4 text-base text-blue-800 mb-4">
                                                        <div class="bg-blue-100 p-2 rounded-lg">
                                                            <FileText class="h-5 w-5 text-blue-600" />
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-lg">{{ doc.replace('_', ' ').toUpperCase() }}</span>
                                                            <p class="text-sm text-blue-600 mt-1">Please upload your {{ doc.replace('_', ' ').toLowerCase() }}</p>
                                                        </div>
                                                    </div>

                                                    <!-- Upload Area for this specific document -->
                                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-blue-400 transition-colors bg-white">
                                                        <input
                                                            type="file"
                                                            multiple
                                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                            @change="(e) => { const files = (e.target as HTMLInputElement).files; if (files) addMultipleSubmittedDocuments(createForm, doc, files); }"
                                                            class="hidden"
                                                            :id="`upload-${doc}`"
                                                        />
                                                        <label :for="`upload-${doc}`" class="cursor-pointer">
                                                            <Upload class="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                                            <p class="text-sm text-gray-600 mb-1 font-medium">Click to upload {{ doc.replace('_', ' ').toLowerCase() }}</p>
                                                            <p class="text-xs text-gray-500">PDF, DOC, DOCX, JPG, PNG (max 10MB each)</p>
                                                        </label>
                                                    </div>

                                                    <!-- Uploaded Files for this specific document -->
                                                    <div v-if="createForm.submitted_documents[doc] && createForm.submitted_documents[doc].length > 0" class="space-y-2">
                                                        <Label class="text-sm font-medium text-gray-700">Uploaded Files:</Label>
                                                        <div v-for="(file, index) in createForm.submitted_documents[doc]" :key="index" class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                                                            <div class="flex items-center gap-3">
                                                                <div class="bg-green-100 p-2 rounded-lg">
                                                                    <FileText class="h-4 w-4 text-green-600" />
                                                                </div>
                                                                <div>
                                                                    <span class="text-sm font-medium text-gray-700">{{ file.name }}</span>
                                                                    <p class="text-xs text-gray-500">({{ (file.size / 1024 / 1024).toFixed(2) }}MB)</p>
                                                                </div>
                                                            </div>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                @click="() => removeSubmittedDocument(createForm, doc, index)"
                                                                class="text-red-600 hover:text-red-700 p-1"
                                                            >
                                                                <X class="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- No Required Documents Case -->
                                            <div v-else class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6 text-center">
                                                <FileText class="h-8 w-8 mx-auto mb-3 text-green-500" />
                                                <p class="font-medium text-green-800 mb-2">No specific documents required</p>
                                                <p class="text-sm text-green-600">This document type doesn't require any specific documents to be uploaded.</p>
                                            </div>

                                            <p class="text-sm text-blue-700 font-medium bg-blue-100 px-4 py-2 rounded-lg">
                                                📋 Please upload all required documents before submitting your request.
                                            </p>
                                        </div>

                                        <div v-if="selectedType && transactionTypes && transactionTypes[selectedType] && transactionTypes[selectedType].fee > 0" class="space-y-4">
                                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6">
                                                <h4 class="font-bold text-blue-900 mb-3 text-lg">💰 Fee Information</h4>
                                                <p class="text-blue-700 text-xl mb-2">
                                                    Processing fee: <span class="font-black text-2xl">₱{{ Number(transactionTypes[selectedType].fee).toFixed(2) }}</span>
                                                </p>
                                                <p class="text-sm text-blue-600 font-medium">
                                                    Payment will be required when your request is approved.
                                                </p>
                                            </div>
                                        </div>

                                        <div v-if="selectedType && transactionTypes && transactionTypes[selectedType] && Number(transactionTypes[selectedType].fee) === 0" class="space-y-4">
                                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6">
                                                <h4 class="font-bold text-green-900 mb-3 text-lg">🎉 No Fee Required</h4>
                                                <p class="text-green-700 text-lg font-medium">
                                                    This document type is free of charge.
                                                </p>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Fixed Footer with Buttons -->
                                <div class="flex-shrink-0 p-6 sm:p-8 border-t border-gray-200 bg-white">
                                    <div class="flex flex-row gap-3 sm:gap-4">
                                        <Button type="button" :disabled="createForm.processing" @click="submitCreate" size="lg" class="flex-1 h-12 sm:h-14 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-base sm:text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300">
                                            {{ createForm.processing ? '⏳ Submitting...' : '🚀 Submit Request' }}
                                        </Button>
                                        <Button type="button" variant="outline" size="lg" @click="sheetOpen = false" class="flex-1 h-12 sm:h-14 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-bold text-base sm:text-lg rounded-xl transition-all duration-300">
                                            Cancel
                                        </Button>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>

                        <!-- View Transaction Sheet -->
                        <Sheet v-model:open="viewSheetOpen">
                            <SheetContent class="w-full sm:w-[580px] lg:w-[640px] p-0 flex flex-col h-full">
                                <div class="flex flex-col h-full">
                                    <!-- Header -->
                                    <div class="flex-shrink-0 px-6 sm:px-8 py-6 border-b border-gray-200 bg-white">
                                        <SheetHeader class="pb-6">
                                            <SheetTitle class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Transaction Details</SheetTitle>
                                            <SheetDescription class="text-base sm:text-lg text-gray-600 leading-relaxed">
                                                View complete information about your document request
                                            </SheetDescription>
                                        </SheetHeader>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-6" v-if="selectedTransaction">
                                        <div class="space-y-6">
                                            <!-- Status Badge -->
                                            <div class="flex items-center justify-center">
                                                <Badge :class="getStatusColor(selectedTransaction.status)" class="px-6 py-3 text-lg font-bold rounded-xl flex items-center gap-2">
                                                    <component :is="getStatusIcon(selectedTransaction.status)" class="h-5 w-5" />
                                                    {{ selectedTransaction.status.replace('_', ' ').toUpperCase() }}
                                                </Badge>
                                            </div>

                                            <!-- Transaction Info -->
                                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-blue-900 mb-4">📋 Request Information</h3>
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-3">
                                                        <FileText class="h-5 w-5 text-blue-600" />
                                                        <span class="font-semibold text-blue-800">Type:</span>
                                                        <span class="text-blue-700">{{ getTypeName(selectedTransaction.type, props.transactionTypes) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="font-semibold text-blue-800">Title:</span>
                                                        <span class="text-blue-700">{{ selectedTransaction.title }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="font-semibold text-blue-800">ID:</span>
                                                        <span class="text-blue-700 font-mono">{{ selectedTransaction.transaction_id }}</span>
                                                    </div>
                                                    <div class="flex items-start gap-3">
                                                        <span class="font-semibold text-blue-800">Description:</span>
                                                        <span class="text-blue-700">{{ selectedTransaction.description || 'No description provided' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Progressive Timeline -->
                                            <div class="bg-gradient-to-r from-gray-50 to-slate-50 border-2 border-gray-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-gray-900 mb-6">⏰ Transaction Progress</h3>

                                                <!-- Progress Steps -->
                                                <div class="relative">
                                                    <!-- Progress Line -->
                                                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                                                    <!-- Step 1: Submitted -->
                                                    <div class="relative flex items-start gap-4 pb-6">
                                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 border-4 border-green-200 flex items-center justify-center shadow-lg">
                                                            <Calendar class="h-6 w-6 text-green-600" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="text-lg font-bold text-green-800">Request Submitted</h4>
                                                                <Badge class="bg-green-100 text-green-800 border-green-200 text-xs">Completed</Badge>
                                                            </div>
                                                            <p class="text-green-700 font-medium">{{ formatDateShort(selectedTransaction.submitted_at) }}</p>
                                                            <p class="text-sm text-green-600 mt-1">Your request has been successfully submitted and is now in our system.</p>
                                                        </div>
                                                    </div>

                                                    <!-- Step 2: Processing -->
                                                    <div class="relative flex items-start gap-4 pb-6">
                                                        <div v-if="selectedTransaction.status === 'pending'" class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 border-4 border-yellow-200 flex items-center justify-center shadow-lg">
                                                            <Clock class="h-6 w-6 text-yellow-600" />
                                                        </div>
                                                        <div v-else-if="selectedTransaction.status === 'in_progress'" class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 border-4 border-blue-200 flex items-center justify-center shadow-lg animate-pulse">
                                                            <AlertCircle class="h-6 w-6 text-blue-600" />
                                                        </div>
                                                        <div v-else-if="selectedTransaction.processed_at" class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 border-4 border-green-200 flex items-center justify-center shadow-lg">
                                                            <CheckCircle class="h-6 w-6 text-green-600" />
                                                        </div>
                                                        <div v-else class="flex-shrink-0 w-12 h-12 rounded-full bg-gray-100 border-4 border-gray-200 flex items-center justify-center shadow-lg">
                                                            <Clock class="h-6 w-6 text-gray-400" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="text-lg font-bold" :class="{
                                                                    'text-yellow-800': selectedTransaction.status === 'pending',
                                                                    'text-blue-800': selectedTransaction.status === 'in_progress',
                                                                    'text-green-800': selectedTransaction.processed_at,
                                                                    'text-gray-600': !selectedTransaction.processed_at && !['pending', 'in_progress'].includes(selectedTransaction.status)
                                                                }">Under Review</h4>
                                                                <Badge v-if="selectedTransaction.status === 'pending'" class="bg-yellow-100 text-yellow-800 border-yellow-200 text-xs">Pending</Badge>
                                                                <Badge v-else-if="selectedTransaction.status === 'in_progress'" class="bg-blue-100 text-blue-800 border-blue-200 text-xs animate-pulse">In Progress</Badge>
                                                                <Badge v-else-if="selectedTransaction.processed_at" class="bg-green-100 text-green-800 border-green-200 text-xs">Completed</Badge>
                                                                <Badge v-else class="bg-gray-100 text-gray-600 border-gray-200 text-xs">Waiting</Badge>
                                                            </div>
                                                            <p v-if="selectedTransaction.processed_at" class="text-green-700 font-medium">{{ formatDateShort(selectedTransaction.processed_at) }}</p>
                                                            <p v-else-if="selectedTransaction.status === 'in_progress'" class="text-blue-700 font-medium">Currently being processed</p>
                                                            <p v-else-if="selectedTransaction.status === 'pending'" class="text-yellow-700 font-medium">Waiting for staff review</p>
                                                            <p v-else class="text-gray-500 font-medium">Not yet started</p>
                                                            <p class="text-sm mt-1" :class="{
                                                                'text-yellow-600': selectedTransaction.status === 'pending',
                                                                'text-blue-600': selectedTransaction.status === 'in_progress',
                                                                'text-green-600': selectedTransaction.processed_at,
                                                                'text-gray-500': !selectedTransaction.processed_at && !['pending', 'in_progress'].includes(selectedTransaction.status)
                                                            }">
                                                                {{ selectedTransaction.status === 'pending' ? 'Your request is in the queue and will be reviewed soon.' :
                                                                   selectedTransaction.status === 'in_progress' ? 'Our staff is actively working on your request.' :
                                                                   selectedTransaction.processed_at ? 'Your request has been reviewed and processed.' :
                                                                   'This step will begin once your request is submitted.' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Step 3: Completion/Rejection -->
                                                    <div v-if="selectedTransaction.status === 'rejected'" class="relative flex items-start gap-4 pb-6">
                                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 border-4 border-red-200 flex items-center justify-center shadow-lg">
                                                            <XCircle class="h-6 w-6 text-red-600" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="text-lg font-bold text-red-800">Request Rejected</h4>
                                                                <Badge class="bg-red-100 text-red-800 border-red-200 text-xs">Rejected</Badge>
                                                            </div>
                                                            <p class="text-red-700 font-medium">{{ formatDateShort(selectedTransaction.processed_at || selectedTransaction.submitted_at) }}</p>
                                                            <p class="text-sm text-red-600 mt-1">Unfortunately, your request could not be approved. Please review the rejection reason below.</p>
                                                        </div>
                                                    </div>
                                                    <div v-else-if="selectedTransaction.completed_at" class="relative flex items-start gap-4 pb-6">
                                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 border-4 border-green-200 flex items-center justify-center shadow-lg">
                                                            <CheckCircle class="h-6 w-6 text-green-600" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="text-lg font-bold text-green-800">Request Completed</h4>
                                                                <Badge class="bg-green-100 text-green-800 border-green-200 text-xs">Completed</Badge>
                                                            </div>
                                                            <p class="text-green-700 font-medium">{{ formatDateShort(selectedTransaction.completed_at) }}</p>
                                                            <p class="text-sm text-green-600 mt-1">Congratulations! Your request has been successfully completed.</p>
                                                        </div>
                                                    </div>
                                                    <div v-else-if="['pending', 'in_progress'].includes(selectedTransaction.status)" class="relative flex items-start gap-4 pb-6">
                                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gray-100 border-4 border-gray-200 flex items-center justify-center shadow-lg">
                                                            <CheckCircle class="h-6 w-6 text-gray-400" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="text-lg font-bold text-gray-600">Request Completion</h4>
                                                                <Badge class="bg-gray-100 text-gray-600 border-gray-200 text-xs">Pending</Badge>
                                                            </div>
                                                            <p class="text-gray-500 font-medium">Not yet completed</p>
                                                            <p class="text-sm text-gray-500 mt-1">This step will be completed once your request is approved and processed.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fee Information -->
                                            <div v-if="Number(selectedTransaction.fee_amount) > 0" class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-yellow-900 mb-4">💰 Fee Information</h3>
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-3">
                                                        <DollarSign class="h-5 w-5 text-yellow-600" />
                                                        <span class="font-semibold text-yellow-800">Amount:</span>
                                                        <span class="text-yellow-700 font-bold text-lg">₱{{ formatFeeAmount(selectedTransaction.fee_amount) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="font-semibold text-yellow-800">Status:</span>
                                                        <Badge v-if="selectedTransaction.fee_paid" class="bg-green-100 text-green-800 border-green-200">
                                                            ✅ Paid
                                                        </Badge>
                                                        <Badge v-else class="bg-yellow-100 text-yellow-800 border-yellow-200">
                                                            ⏳ Pending Payment
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Staff Information -->
                                            <div v-if="selectedTransaction.staff" class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-green-900 mb-4">👤 Assigned Staff</h3>
                                                <div class="flex items-center gap-3">
                                                    <User class="h-5 w-5 text-green-600" />
                                                    <span class="font-semibold text-green-800">Name:</span>
                                                    <span class="text-green-700">{{ selectedTransaction.staff.name }}</span>
                                                </div>
                                            </div>

                                            <!-- Staff Notes -->
                                            <div v-if="selectedTransaction.staff_notes && selectedTransaction.staff_notes.trim()" class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-purple-900 mb-4">📝 Staff Notes</h3>
                                                <p class="text-purple-700">{{ selectedTransaction.staff_notes }}</p>
                                            </div>

                                            <!-- Rejection Reason -->
                                            <div v-if="selectedTransaction.rejection_reason && selectedTransaction.rejection_reason.trim()" class="bg-gradient-to-r from-red-50 to-rose-50 border-2 border-red-200 rounded-2xl p-6">
                                                <h3 class="text-xl font-bold text-red-900 mb-4">❌ Rejection Reason</h3>
                                                <p class="text-red-700">{{ selectedTransaction.rejection_reason }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="flex-shrink-0 p-6 sm:p-8 border-t border-gray-200 bg-white">
                                        <div class="flex flex-row gap-3 sm:gap-4">
                                            <Button
                                                v-if="selectedTransaction?.status === 'pending'"
                                                @click="openEditSheet(selectedTransaction); viewSheetOpen = false"
                                                size="lg"
                                                class="flex-1 h-12 sm:h-14 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-base sm:text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300"
                                            >
                                                ✏️ Edit Request
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="lg"
                                                @click="viewSheetOpen = false"
                                                class="flex-1 h-12 sm:h-14 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-bold text-base sm:text-lg rounded-xl transition-all duration-300"
                                            >
                                                Close
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>

                        <!-- Edit Transaction Sheet -->
                        <Sheet v-model:open="editSheetOpen">
                            <SheetContent class="w-full sm:w-[580px] lg:w-[640px] p-0 flex flex-col h-full">
                                <div class="flex flex-col h-full">
                                    <!-- Header -->
                                    <div class="flex-shrink-0 px-6 sm:px-8 py-6 border-b border-gray-200 bg-white">
                                        <SheetHeader class="pb-6">
                                            <SheetTitle class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Edit Request</SheetTitle>
                                            <SheetDescription class="text-base sm:text-lg text-gray-600 leading-relaxed">
                                                Update your document request information
                                            </SheetDescription>
                                        </SheetHeader>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-6" v-if="editForm">
                                        <form @submit.prevent="submitEdit" class="space-y-6">
                                            <!-- Document Type -->
                                            <div class="space-y-3">
                                                <Label class="text-base font-bold text-gray-800">Document Type</Label>
                                                <Select v-model="editForm.type">
                                                    <SelectTrigger class="h-12 sm:h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                                                        <SelectValue placeholder="Select document type" />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-xl border-2 border-gray-200 shadow-xl">
                                                        <template v-for="(type, key) in props.transactionTypes" :key="key">
                                                            <SelectItem
                                                                v-if="type && type.name && typeof type.fee !== 'undefined'"
                                                                :value="key"
                                                                class="py-4 px-4 hover:bg-blue-50 transition-colors duration-200"
                                                            >
                                                                <div class="flex items-center justify-between w-full">
                                                                    <span class="font-medium">{{ type.name }}</span>
                                                                    <span class="ml-3 text-sm font-bold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">₱{{ Number(type.fee).toFixed(2) }}</span>
                                                                </div>
                                                            </SelectItem>
                                                        </template>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <!-- Title -->
                                            <div class="space-y-3">
                                                <Label class="text-base font-bold text-gray-800">Title</Label>
                                                <Input
                                                    v-model="editForm.title"
                                                    placeholder="Enter document title"
                                                    class="h-12 sm:h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                                />
                                            </div>

                                            <!-- Description -->
                                            <div class="space-y-3">
                                                <Label class="text-base font-bold text-gray-800">Description</Label>
                                                <Textarea
                                                    v-model="editForm.description"
                                                    placeholder="Describe your request..."
                                                    rows="4"
                                                    class="border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 resize-none"
                                                />
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Footer -->
                                    <div class="flex-shrink-0 p-6 sm:p-8 border-t border-gray-200 bg-white">
                                        <div class="flex flex-row gap-3 sm:gap-4">
                                            <Button
                                                type="button"
                                                :disabled="editForm?.processing"
                                                @click="submitEdit"
                                                size="lg"
                                                class="flex-1 h-12 sm:h-14 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-base sm:text-lg rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300"
                                            >
                                                {{ editForm?.processing ? '⏳ Updating...' : '💾 Update Request' }}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="lg"
                                                @click="editSheetOpen = false"
                                                class="flex-1 h-12 sm:h-14 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-bold text-base sm:text-lg rounded-xl transition-all duration-300"
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>

                <!-- Filters Section -->
                <Card class="mb-10 shadow-xl border-0 bg-white/80 backdrop-blur-sm rounded-2xl">
                    <CardHeader class="pb-6">
                        <CardTitle class="text-2xl font-bold text-gray-900">🔍 Filter Transactions</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="space-y-3">
                                <label class="text-base font-bold text-gray-800">Status Filter</label>
                                <Select v-model="filterForm.status" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                                        <SelectValue placeholder="All Status" />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-xl border-2 border-gray-200 shadow-xl">
                                        <SelectItem value="all" class="py-3">All Status</SelectItem>
                                        <SelectItem value="pending" class="py-3">⏳ Pending</SelectItem>
                                        <SelectItem value="in_progress" class="py-3">🔄 In Progress</SelectItem>
                                        <SelectItem value="approved" class="py-3">✅ Approved</SelectItem>
                                        <SelectItem value="rejected" class="py-3">❌ Rejected</SelectItem>
                                        <SelectItem value="completed" class="py-3">🎉 Completed</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-3">
                                <label class="text-base font-bold text-gray-800">Document Type</label>
                                <Select v-model="filterForm.type" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-14 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                                        <SelectValue placeholder="All Types" />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-xl border-2 border-gray-200 shadow-xl">
                                        <SelectItem value="all" class="py-3">All Types</SelectItem>
                                        <SelectItem v-for="(type, key) in transactionTypes" :key="key" :value="key" class="py-3">
                                            {{ type.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Transactions List -->
                <div class="space-y-6">
                    <Card v-for="transaction in transactions" :key="transaction.id" class="shadow-xl hover:shadow-2xl transition-all duration-300 border-0 bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden group">
                        <CardContent class="p-8">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                                <div class="flex-1 space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                        <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">{{ transaction.title }}</h3>
                                        <Badge :class="getStatusColor(transaction.status)" class="w-fit px-4 py-2 rounded-full font-semibold shadow-lg">
                                            <component :is="getStatusIcon(transaction.status)" class="h-4 w-4 mr-2" />
                                            {{ transaction.status.replace('_', ' ').toUpperCase() }}
                                        </Badge>
                                    </div>

                                    <div class="space-y-3">
                                        <p class="text-lg font-semibold text-gray-700">
                                            {{ getTypeName(transaction.type, props.transactionTypes) }} • ID: {{ transaction.transaction_id }}
                                        </p>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-base text-gray-600">
                                            <span class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-full">
                                                <Clock class="h-4 w-4 text-blue-600" />
                                                <span class="font-medium">Submitted: {{ formatDateShort(transaction.submitted_at) }}</span>
                                            </span>
                                            <span v-if="transaction.staff" class="flex items-center gap-2 bg-blue-100 px-4 py-2 rounded-full">
                                                <span class="font-medium text-blue-700">Assigned to: {{ transaction.staff.name }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="Number(transaction.fee_amount) > 0" class="flex items-center gap-3">
                                        <span class="text-lg font-bold text-gray-800 bg-gray-100 px-4 py-2 rounded-full">
                                            Fee: ₱{{ formatFeeAmount(transaction.fee_amount) }}
                                        </span>
                                        <Badge v-if="transaction.fee_paid" class="bg-green-100 text-green-800 border-green-200 px-4 py-2 rounded-full font-semibold">
                                            ✅ Paid
                                        </Badge>
                                        <Badge v-else class="bg-yellow-100 text-yellow-800 border-yellow-200 px-4 py-2 rounded-full font-semibold">
                                            ⏳ Pending Payment
                                        </Badge>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 lg:flex-col lg:min-w-[140px]">
                                    <Button
                                        @click="openViewSheet(transaction)"
                                        variant="outline"
                                        size="lg"
                                        class="w-full h-12 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 border-2 border-blue-200 text-blue-700 font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                                    >
                                        👁️ View Details
                                    </Button>
                                    <Button
                                        v-if="transaction.status === 'pending'"
                                        @click="openEditSheet(transaction)"
                                        variant="outline"
                                        size="lg"
                                        class="w-full h-12 bg-gradient-to-r from-gray-50 to-slate-50 hover:from-gray-100 hover:to-slate-100 border-2 border-gray-200 text-gray-700 font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                                    >
                                        ✏️ Edit
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div v-if="transactions.length === 0" class="text-center py-20">
                        <div class="bg-gradient-to-br from-white to-blue-50/50 rounded-3xl border-2 border-dashed border-blue-200 p-16 shadow-xl">
                            <div class="bg-blue-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                <FileText class="h-12 w-12 text-blue-600" />
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-4">No transactions found</h3>
                            <p class="text-xl text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">Get started by creating your first document request using the "New Request" button above.</p>
                            <div class="text-6xl">📋</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>
