<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Document Type</h1>
                <p class="text-gray-600">{{ documentType.name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="`/staff/document-types/${documentType.id}`">
                    <Button variant="outline" class="flex items-center gap-2">
                        <Eye class="h-4 w-4" />
                        View
                    </Button>
                </Link>
                <Link href="/staff/document-types">
                    <Button variant="ghost" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to List
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Edit Form -->
        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Basic Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label for="code">Code *</Label>
                                    <Input
                                        id="code"
                                        v-model="form.code"
                                        placeholder="e.g., brgy-clearance"
                                        :class="{ 'border-red-500': form.errors.code }"
                                    />
                                    <p v-if="form.errors.code" class="text-sm text-red-600 mt-1">{{ form.errors.code }}</p>
                                </div>
                                <div>
                                    <Label for="name">Name *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="e.g., Barangay Clearance"
                                        :class="{ 'border-red-500': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <Label for="category">Category</Label>
                                    <Select v-model="form.category">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Categories</SelectItem>
                                            <SelectItem value="personal">Personal</SelectItem>
                                            <SelectItem value="business">Business</SelectItem>
                                            <SelectItem value="legal">Legal</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.category" class="text-sm text-red-600 mt-1">{{ form.errors.category }}</p>
                                </div>
                                <div>
                                    <Label for="processing_days">Processing Days *</Label>
                                    <Input
                                        id="processing_days"
                                        v-model.number="form.processing_days"
                                        type="number"
                                        min="1"
                                        :class="{ 'border-red-500': form.errors.processing_days }"
                                    />
                                    <p v-if="form.errors.processing_days" class="text-sm text-red-600 mt-1">{{ form.errors.processing_days }}</p>
                                </div>
                                <div>
                                    <Label for="sort_order">Sort Order</Label>
                                    <Input
                                        id="sort_order"
                                        v-model.number="form.sort_order"
                                        type="number"
                                        min="0"
                                        :class="{ 'border-red-500': form.errors.sort_order }"
                                    />
                                    <p v-if="form.errors.sort_order" class="text-sm text-red-600 mt-1">{{ form.errors.sort_order }}</p>
                                </div>
                                <div>
                                    <Label for="fee_amount">Fee Amount *</Label>
                                    <Input
                                        id="fee_amount"
                                        v-model.number="form.fee_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :class="{ 'border-red-500': form.errors.fee_amount }"
                                    />
                                    <p v-if="form.errors.fee_amount" class="text-sm text-red-600 mt-1">{{ form.errors.fee_amount }}</p>
                                </div>
                            </div>

                            <div>
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Brief description of this document type..."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.description }"
                                />
                                <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">{{ form.errors.description }}</p>
                            </div>

                            <div>
                                <Label for="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Additional notes or instructions..."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.notes }"
                                />
                                <p v-if="form.errors.notes" class="text-sm text-red-600 mt-1">{{ form.errors.notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Required Documents -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileCheck class="h-5 w-5" />
                                Required Documents
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div v-for="(doc, index) in form.required_documents" :key="index" class="flex items-center gap-2">
                                    <Input
                                        v-model="form.required_documents[index]"
                                        placeholder="Enter required document..."
                                        :class="{ 'border-red-500': form.errors[`required_documents.${index}`] }"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="() => removeRequiredDocument(form, index)"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => addRequiredDocument(form)"
                                    class="w-full"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Required Document
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Processing Steps -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Settings class="h-5 w-5" />
                                Processing Steps
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div v-for="(step, index) in form.processing_steps" :key="index" class="flex items-center gap-2">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                        {{ index + 1 }}
                                    </div>
                                    <Input
                                        v-model="form.processing_steps[index]"
                                        placeholder="Enter processing step..."
                                        :class="{ 'border-red-500': form.errors[`processing_steps.${index}`] }"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="() => removeProcessingStep(form, index)"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => addProcessingStep(form)"
                                    class="w-full"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Processing Step
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status & Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="h-5 w-5" />
                                Status & Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Label for="is_active">Active</Label>
                                <Switch
                                    id="is_active"
                                    v-model="form.is_active"
                                />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="requires_payment">Requires Payment</Label>
                                <Switch
                                    id="requires_payment"
                                    v-model="form.requires_payment"
                                />
                            </div>

                            <div class="flex items-center justify-between">
                                <Label for="requires_approval">Requires Approval</Label>
                                <Switch
                                    id="requires_approval"
                                    v-model="form.requires_approval"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Save class="h-5 w-5" />
                                Actions
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                @click="resetFormToInitial"
                                :disabled="form.processing"
                                class="w-full"
                            >
                                <RotateCcw class="h-4 w-4 mr-2" />
                                Reset
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    ArrowLeft,
    Eye,
    FileText,
    FileCheck,
    Settings,
    Activity,
    Save,
    RotateCcw,
    Plus,
    Trash2
} from 'lucide-vue-next';
import { useDocumentTypes, type DocumentType } from '@/composables/useDocumentTypes';

interface Props {
    documentType: DocumentType;
}

const props = defineProps<Props>();

// Composables
const documentTypesComposable = useDocumentTypes() as any;
const {
    createEditForm,
    submitEditForm,
    resetForm,
    addRequiredDocument,
    removeRequiredDocument,
    addProcessingStep,
    removeProcessingStep
} = documentTypesComposable;

// Create edit form
const form: any = createEditForm(props.documentType);

// Form submission
const submitForm = () => {
    submitEditForm(form, props.documentType.id);
};

// Reset form to initial values
const resetFormToInitial = () => {
    resetForm(form, {
        code: props.documentType.code,
        name: props.documentType.name,
        description: props.documentType.description || '',
        fee_amount: props.documentType.fee_amount,
        required_documents: props.documentType.required_documents || [''],
        processing_steps: props.documentType.processing_steps || [''],
        processing_days: props.documentType.processing_days,
        is_active: props.documentType.is_active,
        requires_payment: props.documentType.requires_payment,
        requires_approval: props.documentType.requires_approval,
        category: props.documentType.category || '',
        sort_order: props.documentType.sort_order,
        notes: props.documentType.notes || '',
    });
};
</script>
