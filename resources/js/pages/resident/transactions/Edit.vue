<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import resident from '@/routes/resident';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import FileUpload from '@/components/ui/file-upload.vue';
import InputError from '@/components/InputError.vue';
import { ArrowLeft, AlertTriangle, Upload, X, Save, RotateCcw } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { useFormHandlers } from '@/composables/useFormHandlers';

interface TransactionInputField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    placeholder?: string | null;
    options?: string[];
}

interface TransactionType {
    name: string;
    description: string;
    required_documents: string[];
    fee: number;
    processing_days: number;
    requires_payment: boolean;
    requires_approval: boolean;
    category: string;
    required_fields?: string[];
    input_fields?: TransactionInputField[];
}

interface SubmittedDocumentMeta {
    document_type: string;
    name: string;
    path: string;
    size?: number;
    mime_type?: string;
}

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description: string | null;
    status: string;
    rejection_reason?: string | null;
    fee_amount: number | string;
    fee_paid: boolean;
    submitted_at: string;
    created_at: string;
    required_documents?: string[];
    resident_input_data?: Record<string, string>;
    submitted_documents?: SubmittedDocumentMeta[];
}

interface Props {
    transaction: Transaction;
    transactionTypes: Record<string, TransactionType>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'My Transactions', href: resident.transactions.index().url },
    { title: `Edit ${props.transaction.transaction_id}`, href: resident.transactions.edit(props.transaction.id).url },
];

const {
    createTransactionForm,
    updateFormForTransactionType,
    submitTransactionUpdate,
    addMultipleSubmittedDocuments,
    removeSubmittedDocument,
    transactionDocumentUploadSlots,
} = useFormHandlers();

const form = createTransactionForm();

const isRejected = computed(() => props.transaction.status === 'rejected');

const inputFieldsForForm = computed((): TransactionInputField[] => {
    const t = form.type ? props.transactionTypes[form.type] : null;
    if (!t) {
        return [];
    }
    if (Array.isArray(t.input_fields) && t.input_fields.length > 0) {
        return t.input_fields;
    }
    const rf = t.required_fields;
    if (!Array.isArray(rf)) {
        return [];
    }
    return rf.map((s: string, i: number) => ({
        key: String(s)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '') || `field_${i}`,
        label: String(s),
        type: 'text',
        required: true,
        placeholder: null,
        options: [],
    }));
});

const transactionUploadsBusy = computed(() =>
    Object.values(transactionDocumentUploadSlots.value).some((rows) => rows.some((r) => r.id === '' && !r.error)),
);

const hydrateFormFromTransaction = () => {
    const type = props.transaction.type;

    if (type && props.transactionTypes[type]) {
        updateFormForTransactionType(type, form, props.transactionTypes);
    } else {
        form.type = type || '';
    }

    form.title = props.transaction.title || (type ? props.transactionTypes[type]?.name : '') || '';
    form.description = props.transaction.description || '';
    form.fee_amount = Number(props.transaction.fee_amount ?? props.transactionTypes[type]?.fee ?? 0) || 0;

    const answers = props.transaction.resident_input_data && typeof props.transaction.resident_input_data === 'object'
        ? props.transaction.resident_input_data
        : {};
    Object.keys(answers).forEach((key) => {
        form.required_fields[key] = (answers as any)[key] ?? '';
    });
};

hydrateFormFromTransaction();

watch(
    () => form.type,
    (newType, oldType) => {
        if (!newType || newType === oldType) {
            return;
        }
        if (props.transactionTypes[newType]) {
            updateFormForTransactionType(newType, form, props.transactionTypes);
        }
    },
);

const handleFileUpload = (documentType: string, files: File[]) => {
    void addMultipleSubmittedDocuments(form, documentType, files);
};

const removeFile = (documentType: string, index: number) => {
    void removeSubmittedDocument(form, documentType, index);
};

const submit = () => {
    submitTransactionUpdate(form, resident.transactions.update(props.transaction.id).url, () => {
        window.location.href = resident.transactions.show(props.transaction.id).url;
    });
};
</script>

<template>
    <Head :title="`Edit ${transaction.transaction_id}`" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-4">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="resident.transactions.index().url">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Transactions
                            </Link>
                        </Button>
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <RotateCcw v-if="isRejected" class="h-5 w-5 text-red-600" />
                                {{ isRejected ? 'Resubmit Transaction' : 'Edit Transaction' }}
                            </CardTitle>
                            <CardDescription v-if="isRejected">
                                Update the details below and resubmit. This will send your request back for review (no need to create a new transaction).
                            </CardDescription>
                            <CardDescription v-else>
                                Update your request details.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="isRejected && transaction.rejection_reason"
                        class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                    >
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="h-5 w-5 mt-0.5 shrink-0 text-red-600" />
                            <div class="min-w-0">
                                <p class="font-semibold">Rejected reason</p>
                                <p class="mt-1 whitespace-pre-line break-words">{{ transaction.rejection_reason }}</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Transaction Type -->
                        <div class="space-y-2">
                            <Label>Document Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select a document type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Available Documents</SelectLabel>
                                        <SelectItem
                                            v-for="(type, key) in transactionTypes"
                                            :key="key"
                                            :value="key"
                                        >
                                            {{ type.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <!-- Title --> 
                        <div class="space-y-2"> 
                            <Label>Title</Label> 
                            <Input 
                                v-model="form.title" 
                                placeholder="Auto-generated from document type" 
                                readonly 
                                class="read-only:bg-gray-50 read-only:text-gray-700 read-only:cursor-not-allowed" 
                            /> 
                            <p class="text-xs text-gray-500">Title is auto-filled based on the selected document type.</p> 
                            <InputError :message="form.errors.title" /> 
                        </div> 

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label>Description (optional)</Label>
                            <Textarea v-model="form.description" rows="4" placeholder="Add details about your request (optional)" />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Dynamic Required Info -->
                        <div v-if="inputFieldsForForm.length" class="space-y-4">
                            <h3 class="font-semibold text-sm text-gray-900">Required Information</h3>
                            <div class="space-y-4">
                                <div v-for="field in inputFieldsForForm" :key="field.key" class="border rounded-lg p-4">
                                    <Label class="text-sm font-medium">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </Label>
                                    <div class="mt-2">
                                        <Input
                                            v-if="field.type === 'text'"
                                            v-model="form.required_fields[field.key]"
                                            :placeholder="field.placeholder || undefined"
                                            :required="field.required"
                                        />
                                        <Textarea
                                            v-else-if="field.type === 'textarea'"
                                            v-model="form.required_fields[field.key]"
                                            :placeholder="field.placeholder || undefined"
                                            :required="field.required"
                                            rows="3"
                                        />
                                        <Select v-else-if="field.type === 'select' && field.options?.length" v-model="form.required_fields[field.key]">
                                            <SelectTrigger class="w-full">
                                                <SelectValue :placeholder="field.placeholder || 'Select an option'" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="(form.errors as any)[`required_fields.${field.key}`]" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supporting Documents (Optional) -->
                        <div v-if="form.required_documents.length > 0" class="space-y-3">
                            <h3 class="font-semibold text-sm text-gray-900">Supporting Documents (Optional)</h3>
                            <p class="text-xs text-gray-600">
                                You can submit without uploading. If available, upload the following to help speed up processing.
                            </p>

                            <div class="space-y-4">
                                <div
                                    v-for="document in form.required_documents"
                                    :key="document"
                                    class="border rounded-lg p-4"
                                >
                                    <Label class="text-sm font-medium">{{ document }}</Label>
                                    <div class="mt-2">
                                        <FileUpload
                                            :accept="'.pdf,.doc,.docx,.jpg,.jpeg,.png'"
                                            :multiple="true"
                                            @upload="(files) => handleFileUpload(document, files)"
                                        />

                                        <div v-if="transactionDocumentUploadSlots[document]?.length" class="mt-2 space-y-2">
                                            <div
                                                v-for="(slot, index) in transactionDocumentUploadSlots[document]"
                                                :key="slot.id || `u-${document}-${index}`"
                                                class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm"
                                            >
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <Upload class="h-4 w-4 shrink-0" />
                                                        <span class="truncate">{{ slot.name }}</span>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        :disabled="slot.id === ''"
                                                        class="h-7 w-7 p-0"
                                                        @click="removeFile(document, index)"
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </Button>
                                                </div>
                                                <p v-if="slot.id === ''" class="text-xs text-blue-600 mt-1">Uploading... {{ slot.progress }}%</p>
                                                <div v-if="slot.id === ''" class="mt-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-600 rounded-full transition-[width]" :style="{ width: `${slot.progress}%` }" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                :disabled="form.processing || !form.type || !form.title || transactionUploadsBusy"
                                class="min-w-[140px]"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                {{ form.processing ? (isRejected ? 'Resubmitting...' : 'Updating...') : (isRejected ? 'Resubmit' : 'Update') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </ResidentLayout>
</template>
