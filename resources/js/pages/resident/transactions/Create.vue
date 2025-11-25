<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
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

interface TransactionType {
    name: string;
    description: string;
    required_documents: string[];
    fee: number;
    processing_days: number;
    requires_payment: boolean;
    requires_approval: boolean;
    category: string;
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

const selectedType = ref<string>('');
const uploadedFiles = ref<Record<string, File[]>>({});

const form = useForm({
    type: '',
    title: '',
    description: '',
    required_documents: [] as string[],
    submitted_documents: {} as Record<string, File[]>,
    fee_amount: 0,
});

watch(() => form.type, (newType) => {
    if (newType && props.transactionTypes[newType]) {
        const typeData = props.transactionTypes[newType];
        form.title = typeData.name;
        form.fee_amount = typeData.fee;
        form.required_documents = typeData.required_documents || [];

        // Initialize uploaded files for each required document
        uploadedFiles.value = {};
        typeData.required_documents?.forEach(doc => {
            uploadedFiles.value[doc] = [];
        });
    }
});

const handleFileUpload = (documentType: string, files: File[]) => {
    uploadedFiles.value[documentType] = files;
    form.submitted_documents[documentType] = files;
};

const removeFile = (documentType: string, index: number) => {
    uploadedFiles.value[documentType].splice(index, 1);
    form.submitted_documents[documentType] = uploadedFiles.value[documentType];
};

const submit = () => {
    form.post('/resident/transactions', {
        onSuccess: () => {
            form.reset();
            selectedType.value = '';
            uploadedFiles.value = {};
        },
        onError: (errors) => {
            console.error('Form submission error:', errors);
        }
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
                                            <div v-if="uploadedFiles[document]?.length > 0" class="mt-2">
                                                <div class="flex flex-wrap gap-2">
                                                    <div
                                                        v-for="(file, index) in uploadedFiles[document]"
                                                        :key="index"
                                                        class="flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-md text-sm"
                                                    >
                                                        <Upload class="h-4 w-4" />
                                                        {{ file.name }}
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="removeFile(document, index)"
                                                            class="h-4 w-4 p-0"
                                                        >
                                                            <X class="h-3 w-3" />
                                                        </Button>
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
                                :disabled="form.processing || !form.type"
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
