<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import FileUpload from '@/components/ui/file-upload.vue';
import InputError from '@/components/InputError.vue';
import { ArrowLeft, Upload, X } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
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

interface Props {
    transactionTypes: Record<string, TransactionType>;
}

const props = defineProps<Props>();

const breadcrumbs = useBreadcrumbs(() => [
    { label: 'Dashboard', href: '/resident/dashboard', title: 'Dashboard' },
    { label: 'Transactions', href: '/resident/transactions', title: 'Transactions' },
    { label: 'Create Transaction', href: '/resident/transactions/create', title: 'Create Transaction' },
]);

const {
    createTransactionForm,
    updateFormForTransactionType,
    submitTransactionCreate,
    addMultipleSubmittedDocuments,
    removeSubmittedDocument,
    transactionDocumentUploadSlots,
} = useFormHandlers();

const form = createTransactionForm();

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

watch(
    () => form.type,
    (newType) => {
        if (newType && props.transactionTypes[newType]) {
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
    submitTransactionCreate(form, '/resident/transactions', () => {
        //
    });
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'pending': return 'text-yellow-600 bg-yellow-50';
        case 'in_progress': return 'text-blue-600 bg-blue-50';
        case 'approved': return 'text-green-600 bg-green-50';
        case 'completed': return 'text-emerald-600 bg-emerald-50';
        case 'rejected': return 'text-red-600 bg-red-50';
        default: return 'text-gray-600 bg-gray-50';
    }
};
</script>

<template>
    <Head title="Create Transaction" />
    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-4">
                        <Button variant="ghost" size="sm" as-child>
                            <a href="/resident/transactions">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Transactions
                            </a>
                        </Button>
                        <div>
                            <CardTitle>Create New Transaction</CardTitle>
                            <CardDescription>Submit a new document request to the barangay office.</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Transaction Type Selection -->
                        <div>
                            <Label for="type">Transaction Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger id="type">
                                    <SelectValue placeholder="Select a transaction type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Available Services</SelectLabel>
                                        <SelectItem
                                            v-for="(typeData, typeCode) in transactionTypes"
                                            :key="typeCode"
                                            :value="typeCode"
                                        >
                                            {{ typeData.name }}
                                            <span v-if="typeData.fee > 0" class="text-sm text-gray-500 ml-2">
                                                (₱{{ typeData.fee }})
                                            </span>
                                            <span v-else class="text-sm text-green-600 ml-2">(Free)</span>
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <!-- Transaction Details -->
                        <div v-if="form.type" class="space-y-4">
                            <div>
                                <Label for="title">Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter transaction title"
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>

                            <div>
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Provide additional details about your request"
                                    rows="3"
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div v-if="form.type && inputFieldsForForm.length" class="space-y-4">
                                <h3 class="font-semibold text-sm text-gray-900">Required information</h3>
                                <div
                                    v-for="field in inputFieldsForForm"
                                    :key="field.key"
                                    class="rounded-lg border border-gray-200 bg-white p-4 space-y-2"
                                >
                                    <Label :for="`rf-${field.key}`" class="text-sm font-medium">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        v-if="field.type === 'text'"
                                        :id="`rf-${field.key}`"
                                        v-model="form.required_fields[field.key]"
                                        :placeholder="field.placeholder || undefined"
                                        :required="field.required"
                                    />
                                    <Input
                                        v-else-if="field.type === 'number'"
                                        :id="`rf-${field.key}`"
                                        v-model="form.required_fields[field.key]"
                                        type="number"
                                        :placeholder="field.placeholder || undefined"
                                        :required="field.required"
                                    />
                                    <Input
                                        v-else-if="field.type === 'date' || field.type === 'email'"
                                        :id="`rf-${field.key}`"
                                        v-model="form.required_fields[field.key]"
                                        :type="field.type"
                                        :placeholder="field.placeholder || undefined"
                                        :required="field.required"
                                    />
                                    <Textarea
                                        v-else-if="field.type === 'textarea'"
                                        :id="`rf-${field.key}`"
                                        v-model="form.required_fields[field.key]"
                                        :placeholder="field.placeholder || undefined"
                                        :required="field.required"
                                        rows="3"
                                    />
                                    <Select v-else-if="field.type === 'select'" v-model="form.required_fields[field.key]">
                                        <SelectTrigger :id="`rf-${field.key}`" class="w-full">
                                            <SelectValue :placeholder="field.placeholder || 'Choose…'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="opt in field.options || []"
                                                :key="`${field.key}-${opt}`"
                                                :value="opt"
                                            >
                                                {{ opt }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors[`required_fields.${field.key}`]" class="mt-1" />
                                </div>
                            </div>

                            <!-- Transaction Type Information -->
                            <div v-if="transactionTypes[form.type]" class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-sm text-gray-900 mb-2">Service Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium">Processing Time:</span>
                                        <span class="ml-2">{{ transactionTypes[form.type].processing_days }} days</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Fee:</span>
                                        <span class="ml-2" :class="transactionTypes[form.type].fee > 0 ? 'text-gray-900' : 'text-green-600'">
                                            {{ transactionTypes[form.type].fee > 0 ? '₱' + transactionTypes[form.type].fee : 'Free' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Payment Required:</span>
                                        <span class="ml-2" :class="transactionTypes[form.type].requires_payment ? 'text-red-600' : 'text-green-600'">
                                            {{ transactionTypes[form.type].requires_payment ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Approval Required:</span>
                                        <span class="ml-2" :class="transactionTypes[form.type].requires_approval ? 'text-yellow-600' : 'text-green-600'">
                                            {{ transactionTypes[form.type].requires_approval ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="transactionTypes[form.type].description" class="mt-2">
                                    <span class="font-medium">Description:</span>
                                    <p class="text-gray-700 mt-1">{{ transactionTypes[form.type].description }}</p>
                                </div>
                            </div>

                            <!-- Required Documents Upload -->
                            <div v-if="form.required_documents.length > 0">
                                <h3 class="font-semibold text-sm text-gray-900 mb-3">Required Documents</h3>
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
                                                    <p v-if="slot.id === ''" class="text-xs text-blue-600 mt-1">Uploading… {{ slot.progress }}%</p>
                                                    <div v-if="slot.id === ''" class="mt-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-blue-600 rounded-full transition-[width]" :style="{ width: `${slot.progress}%` }" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                :disabled="form.processing || !form.type || transactionUploadsBusy"
                                class="min-w-[120px]"
                            >
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </ResidentLayout>
</template>
