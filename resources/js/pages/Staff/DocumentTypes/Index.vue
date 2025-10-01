<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Plus, Search, Edit, Eye, Trash2, ToggleLeft, ToggleRight, Filter, AlertTriangle, CheckCircle, Save } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useDocumentTypes, type DocumentType } from '@/composables/useDocumentTypes';
import { useDocumentTypeFilters } from '@/composables/useDocumentTypeFilters';
import { useUtils } from '@/composables/useUtils';

interface Props {
    documentTypes: {
        data: DocumentType[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    categories: string[];
    filters: {
        search?: string;
        category?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

// Composables
const { staffDocumentTypesBreadcrumbs } = useBreadcrumbs();
const { formatPeso, formatDateShort } = useUtils();
const {
    showDeleteDialog,
    documentTypeToDelete,
    deleteForm,
    openDeleteDialog,
    confirmDelete,
    closeDeleteDialog,
    toggleStatus,
    createDocumentTypeForm,
    createEditForm,
    submitCreateForm,
    submitEditForm,
    addRequiredDocument,
    removeRequiredDocument,
    addProcessingStep,
    removeProcessingStep
} = useDocumentTypes();
const {
    searchQuery,
    filterForm,
    applyFilters,
    clearFilters,
    createDebouncedSearch,
    goToNextPage,
    goToPreviousPage,
    categoryOptions,
    statusOptions
} = useDocumentTypeFilters(props.filters);

// Breadcrumbs
const breadcrumbs = staffDocumentTypesBreadcrumbs.value;

// Sheet state management
const showCreateSheet = ref(false);
const showEditSheet = ref(false);
const showViewSheet = ref(false);
const selectedDocumentType = ref<DocumentType | null>(null);

// Forms
const createForm = createDocumentTypeForm();
const editForm = ref<any>(null);

// Initialize debounced search
createDebouncedSearch();

// Sheet methods
const openCreateSheet = () => {
    showCreateSheet.value = true;
};

const closeCreateSheet = () => {
    showCreateSheet.value = false;
    createForm.reset();
};

const openEditSheet = (documentType: DocumentType) => {
    selectedDocumentType.value = documentType;
    editForm.value = createEditForm(documentType);
    showEditSheet.value = true;
};

const closeEditSheet = () => {
    showEditSheet.value = false;
    selectedDocumentType.value = null;
    editForm.value = null;
};

const openViewSheet = (documentType: DocumentType) => {
    selectedDocumentType.value = documentType;
    showViewSheet.value = true;
};

const closeViewSheet = () => {
    showViewSheet.value = false;
    selectedDocumentType.value = null;
};

const handleCreateSubmit = () => {
    submitCreateForm(createForm);
};

const handleEditSubmit = () => {
    if (editForm.value && selectedDocumentType.value) {
        submitEditForm(editForm.value, selectedDocumentType.value.id);
    }
};


</script>

<template>
    <Head title="Document Types" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header Section -->
                <div class="mb-6 md:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 md:gap-6">
                        <div class="space-y-2">
                            <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                                Document Types
                            </h1>
                            <p class="text-lg md:text-xl text-gray-600 font-medium">Manage document types and their requirements</p>
                        </div>
                        <div class="flex gap-3">
                            <Button
                                @click="openCreateSheet"
                                size="lg"
                                class="shadow-xl hover:shadow-2xl transition-all duration-300 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white border-0 px-4 md:px-8 py-3 md:py-4 rounded-xl font-semibold text-sm md:text-base"
                            >
                                <Plus class="h-5 w-5 mr-2" />
                                Add Document Type
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Document Types Table -->
                <Card class="shadow-lg border-gray-200">
                    <CardHeader>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <CardTitle class="flex items-center gap-2">
                                <span>Document Types ({{ props.documentTypes.total }})</span>
                            </CardTitle>

                            <!-- Filters -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <!-- Search -->
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search document types..."
                                        class="pl-10 h-10 w-64 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <!-- Category Filter -->
                                <Select v-model="filterForm.category" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-10 w-40 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <SelectValue placeholder="Category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Categories</SelectItem>
                                        <SelectItem v-for="category in categories" :key="category" :value="category">
                                            {{ category }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Status Filter -->
                                <Select v-model="filterForm.status" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-10 w-32 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Clear Filters -->
                                <Button @click="clearFilters" variant="outline" size="sm" class="h-10 border-gray-200 hover:bg-gray-50">
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-gray-50">
                                        <TableHead class="font-semibold text-gray-900">Name</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Category</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Fee</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Processing Days</TableHead>
                                        <TableHead class="font-semibold text-gray-900">Status</TableHead>
                                        <TableHead class="font-semibold text-gray-900 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="documentType in props.documentTypes.data" :key="documentType.id" class="hover:bg-gray-50">
                                        <TableCell class="py-4 px-4 pr-8">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ documentType.name }}</div>
                                                <div v-if="documentType.description" class="text-xs text-gray-400 truncate max-w-xs">
                                                    {{ documentType.description }}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <span v-if="documentType.category" class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                                                {{ documentType.category }}
                                            </span>
                                            <span v-else class="text-sm text-gray-400">-</span>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900">{{ formatPeso(documentType.fee_amount) }}</span>
                                                <Badge v-if="!documentType.requires_payment" variant="secondary" class="text-xs">
                                                    Free
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <span class="text-sm text-gray-600">{{ documentType.processing_days }} days</span>
                                        </TableCell>
                                        <TableCell class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <Badge :class="documentType.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                                    {{ documentType.is_active ? 'Active' : 'Inactive' }}
                                                </Badge>
                                                <Button
                                                    @click="toggleStatus(documentType)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0 hover:bg-gray-100"
                                                >
                                                    <ToggleRight v-if="documentType.is_active" class="h-4 w-4 text-green-600" />
                                                    <ToggleLeft v-else class="h-4 w-4 text-gray-400" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-4 px-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button
                                                    @click="openViewSheet(documentType)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0 hover:bg-blue-50"
                                                >
                                                    <Eye class="h-4 w-4 text-blue-600" />
                                                </Button>
                                                <Button
                                                    @click="openEditSheet(documentType)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0 hover:bg-yellow-50"
                                                >
                                                    <Edit class="h-4 w-4 text-yellow-600" />
                                                </Button>
                                                <Button
                                                    @click="openDeleteDialog(documentType)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0 hover:bg-red-50"
                                                >
                                                    <Trash2 class="h-4 w-4 text-red-600" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="props.documentTypes.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
                            <div class="text-sm text-gray-700">
                                Showing {{ props.documentTypes.from }} to {{ props.documentTypes.to }} of {{ props.documentTypes.total }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="props.documentTypes.current_page > 1"
                                    @click="goToPreviousPage(props.documentTypes.current_page)"
                                    variant="outline"
                                    size="sm"
                                >
                                    Previous
                                </Button>
                                <span class="text-sm text-gray-600">
                                    Page {{ props.documentTypes.current_page }} of {{ props.documentTypes.last_page }}
                                </span>
                                <Button
                                    v-if="props.documentTypes.current_page < props.documentTypes.last_page"
                                    @click="goToNextPage(props.documentTypes.current_page, props.documentTypes.last_page)"
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

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        Delete Document Type
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ documentTypeToDelete?.name }}</strong>?
                        This action cannot be undone and will permanently remove this document type from the system.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="flex gap-4 pt-6 mt-6 border-t border-gray-200">
                    <Button
                        variant="outline"
                        @click="closeDeleteDialog"
                        :disabled="deleteForm.processing"
                        class="flex-1 h-11 text-gray-700 hover:bg-gray-50 border-gray-300"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmDelete"
                        :disabled="deleteForm.processing"
                        class="flex-1 h-11 bg-red-600 hover:bg-red-700 text-white border-0"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create Document Type Sheet -->
        <Sheet v-model:open="showCreateSheet">
            <SheetContent class="w-full sm:max-w-2xl overflow-y-auto p-6">
                <SheetHeader class="mb-8">
                    <SheetTitle class="flex items-center gap-2 mb-2">
                        <Plus class="h-5 w-5" />
                        Create Document Type
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Add a new document type to the system
                    </SheetDescription>
                </SheetHeader>

                <form @submit.prevent="handleCreateSubmit" class="space-y-8">
                    <!-- Basic Information -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="create-name" class="text-sm font-medium text-gray-700">Name *</Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    placeholder="e.g., Barangay Clearance"
                                    class="h-11"
                                    :class="{ 'border-red-500': createForm.errors.name }"
                                />
                                <p v-if="createForm.errors.name" class="text-sm text-red-600 mt-2">{{ createForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="create-category" class="text-sm font-medium text-gray-700">Category</Label>
                                <Select v-model="createForm.category">
                                    <SelectTrigger class="h-11">
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
                            </div>
                            <div class="space-y-2">
                                <Label for="create-processing-days" class="text-sm font-medium text-gray-700">Processing Days *</Label>
                                <Input
                                    id="create-processing-days"
                                    v-model.number="createForm.processing_days"
                                    type="number"
                                    min="1"
                                    class="h-11"
                                    :class="{ 'border-red-500': createForm.errors.processing_days }"
                                />
                                <p v-if="createForm.errors.processing_days" class="text-sm text-red-600 mt-2">{{ createForm.errors.processing_days }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="create-fee" class="text-sm font-medium text-gray-700">Fee Amount *</Label>
                                <Input
                                    id="create-fee"
                                    v-model.number="createForm.fee_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="h-11"
                                    :class="{ 'border-red-500': createForm.errors.fee_amount }"
                                />
                                <p v-if="createForm.errors.fee_amount" class="text-sm text-red-600 mt-2">{{ createForm.errors.fee_amount }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="create-sort" class="text-sm font-medium text-gray-700">Sort Order</Label>
                                <Input
                                    id="create-sort"
                                    v-model.number="createForm.sort_order"
                                    type="number"
                                    min="0"
                                    class="h-11"
                                />
                            </div>
                        </div>

                        <div class="space-y-2 mt-6">
                            <Label for="create-description" class="text-sm font-medium text-gray-700">Description</Label>
                            <Textarea
                                id="create-description"
                                v-model="createForm.description"
                                placeholder="Brief description of this document type..."
                                rows="3"
                                class="resize-none"
                            />
                        </div>

                        <div class="space-y-2 mt-6">
                            <Label for="create-notes" class="text-sm font-medium text-gray-700">Notes</Label>
                            <Textarea
                                id="create-notes"
                                v-model="createForm.notes"
                                placeholder="Additional notes or instructions..."
                                rows="3"
                                class="resize-none"
                            />
                        </div>
                    </div>

                    <!-- Status & Settings -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Status & Settings</h3>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="create-active" class="text-sm font-medium text-gray-700">Active</Label>
                                <Switch id="create-active" v-model="createForm.is_active" />
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="create-payment" class="text-sm font-medium text-gray-700">Requires Payment</Label>
                                <Switch id="create-payment" v-model="createForm.requires_payment" />
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="create-approval" class="text-sm font-medium text-gray-700">Requires Approval</Label>
                                <Switch id="create-approval" v-model="createForm.requires_approval" />
                            </div>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Required Documents</h3>
                        <div class="space-y-4">
                            <div v-for="(doc, index) in createForm.required_documents" :key="index" class="flex items-center gap-3 p-4 bg-white rounded-lg border">
                                <Input
                                    v-model="createForm.required_documents[index]"
                                    placeholder="Enter required document..."
                                    class="flex-1 h-11"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => removeRequiredDocument(createForm, index)"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50 h-11 w-11"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="() => addRequiredDocument(createForm)"
                                class="w-full h-11 border-dashed border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Required Document
                            </Button>
                        </div>
                    </div>

                    <!-- Processing Steps -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Processing Steps</h3>
                        <div class="space-y-4">
                            <div v-for="(step, index) in createForm.processing_steps" :key="index" class="flex items-center gap-4 p-4 bg-white rounded-lg border">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ index + 1 }}
                                </div>
                                <Input
                                    v-model="createForm.processing_steps[index]"
                                    placeholder="Enter processing step..."
                                    class="flex-1 h-11"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => removeProcessingStep(createForm, index)"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50 h-11 w-11"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="() => addProcessingStep(createForm)"
                                class="w-full h-11 border-dashed border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Processing Step
                            </Button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-8 mt-10 border-t border-gray-200">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeCreateSheet"
                            :disabled="createForm.processing"
                            class="flex-1 h-12 text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="flex-1 h-12 bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            {{ createForm.processing ? 'Creating...' : 'Create Document Type' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Edit Document Type Sheet -->
        <Sheet v-model:open="showEditSheet">
            <SheetContent class="w-full sm:max-w-2xl overflow-y-auto p-6">
                <SheetHeader class="mb-8">
                    <SheetTitle class="flex items-center gap-2 mb-2">
                        <Edit class="h-5 w-5" />
                        Edit Document Type
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Update {{ selectedDocumentType?.name }} information
                    </SheetDescription>
                </SheetHeader>

                <form v-if="editForm" @submit.prevent="handleEditSubmit" class="space-y-8">
                    <!-- Basic Information -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="edit-name" class="text-sm font-medium text-gray-700">Name *</Label>
                                <Input
                                    id="edit-name"
                                    v-model="editForm.name"
                                    placeholder="e.g., Barangay Clearance"
                                    class="h-11"
                                    :class="{ 'border-red-500': editForm.errors.name }"
                                />
                                <p v-if="editForm.errors.name" class="text-sm text-red-600 mt-2">{{ editForm.errors.name }}</p>
                            </div>
                            <div>
                                <Label for="edit-category">Category</Label>
                                <Select v-model="editForm.category">
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
                            </div>
                            <div>
                                <Label for="edit-processing-days">Processing Days *</Label>
                                <Input
                                    id="edit-processing-days"
                                    v-model.number="editForm.processing_days"
                                    type="number"
                                    min="1"
                                    :class="{ 'border-red-500': editForm.errors.processing_days }"
                                />
                                <p v-if="editForm.errors.processing_days" class="text-sm text-red-600 mt-1">{{ editForm.errors.processing_days }}</p>
                            </div>
                            <div>
                                <Label for="edit-fee">Fee Amount *</Label>
                                <Input
                                    id="edit-fee"
                                    v-model.number="editForm.fee_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :class="{ 'border-red-500': editForm.errors.fee_amount }"
                                />
                                <p v-if="editForm.errors.fee_amount" class="text-sm text-red-600 mt-1">{{ editForm.errors.fee_amount }}</p>
                            </div>
                            <div>
                                <Label for="edit-sort">Sort Order</Label>
                                <Input
                                    id="edit-sort"
                                    v-model.number="editForm.sort_order"
                                    type="number"
                                    min="0"
                                />
                            </div>
                        </div>

                        <div>
                            <Label for="edit-description">Description</Label>
                            <Textarea
                                id="edit-description"
                                v-model="editForm.description"
                                placeholder="Brief description of this document type..."
                                rows="3"
                            />
                        </div>

                        <div>
                            <Label for="edit-notes">Notes</Label>
                            <Textarea
                                id="edit-notes"
                                v-model="editForm.notes"
                                placeholder="Additional notes or instructions..."
                                rows="3"
                            />
                        </div>
                    </div>

                    <!-- Status & Settings -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Status & Settings</h3>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="edit-active" class="text-sm font-medium text-gray-700">Active</Label>
                                <Switch id="edit-active" v-model="editForm.is_active" />
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="edit-payment" class="text-sm font-medium text-gray-700">Requires Payment</Label>
                                <Switch id="edit-payment" v-model="editForm.requires_payment" />
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <Label for="edit-approval" class="text-sm font-medium text-gray-700">Requires Approval</Label>
                                <Switch id="edit-approval" v-model="editForm.requires_approval" />
                            </div>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Required Documents</h3>
                        <div class="space-y-4">
                            <div v-for="(doc, index) in editForm.required_documents" :key="index" class="flex items-center gap-3 p-4 bg-white rounded-lg border">
                                <Input
                                    v-model="editForm.required_documents[index]"
                                    placeholder="Enter required document..."
                                    class="flex-1 h-11"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => removeRequiredDocument(editForm, index)"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50 h-11 w-11"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="() => addRequiredDocument(editForm)"
                                class="w-full h-11 border-dashed border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Required Document
                            </Button>
                        </div>
                    </div>

                    <!-- Processing Steps -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Processing Steps</h3>
                        <div class="space-y-4">
                            <div v-for="(step, index) in editForm.processing_steps" :key="index" class="flex items-center gap-4 p-4 bg-white rounded-lg border">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ index + 1 }}
                                </div>
                                <Input
                                    v-model="editForm.processing_steps[index]"
                                    placeholder="Enter processing step..."
                                    class="flex-1 h-11"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => removeProcessingStep(editForm, index)"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50 h-11 w-11"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="() => addProcessingStep(editForm)"
                                class="w-full h-11 border-dashed border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Processing Step
                            </Button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-8 mt-10 border-t border-gray-200">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditSheet"
                            :disabled="editForm.processing"
                            class="flex-1 h-12 text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="flex-1 h-12 bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- View Document Type Sheet -->
        <Sheet v-model:open="showViewSheet">
            <SheetContent class="w-full sm:max-w-2xl overflow-y-auto p-6">
                <SheetHeader class="mb-8">
                    <SheetTitle class="flex items-center gap-2 mb-2">
                        <Eye class="h-5 w-5" />
                        {{ selectedDocumentType?.name }}
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Document Type Details
                    </SheetDescription>
                </SheetHeader>

                <div v-if="selectedDocumentType" class="space-y-8">
                    <!-- Basic Information -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Code</Label>
                                <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border font-mono">{{ selectedDocumentType.code }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Category</Label>
                                <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border">{{ selectedDocumentType.category || 'Not specified' }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Processing Days</Label>
                                <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border">{{ selectedDocumentType.processing_days }} days</p>
                            </div>
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Sort Order</Label>
                                <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border">{{ selectedDocumentType.sort_order }}</p>
                            </div>
                        </div>

                        <div class="space-y-2 mt-6">
                            <Label class="text-sm font-medium text-gray-700">Description</Label>
                            <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border min-h-[3rem]">{{ selectedDocumentType.description || 'No description provided' }}</p>
                        </div>

                        <div v-if="selectedDocumentType.notes" class="space-y-2 mt-6">
                            <Label class="text-sm font-medium text-gray-700">Notes</Label>
                            <p class="text-sm text-gray-900 bg-white px-4 py-3 rounded-lg border min-h-[3rem]">{{ selectedDocumentType.notes }}</p>
                        </div>
                    </div>

                    <!-- Status & Settings -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Status & Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <span class="text-sm font-medium text-gray-700">Status</span>
                                <Badge :variant="selectedDocumentType.is_active ? 'default' : 'secondary'" class="px-3 py-1">
                                    {{ selectedDocumentType.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <span class="text-sm font-medium text-gray-700">Requires Payment</span>
                                <Badge :variant="selectedDocumentType.requires_payment ? 'default' : 'secondary'" class="px-3 py-1">
                                    {{ selectedDocumentType.requires_payment ? 'Yes' : 'No' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                                <span class="text-sm font-medium text-gray-700">Requires Approval</span>
                                <Badge :variant="selectedDocumentType.requires_approval ? 'default' : 'secondary'" class="px-3 py-1">
                                    {{ selectedDocumentType.requires_approval ? 'Yes' : 'No' }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Information -->
                    <div class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Fee Information</h3>
                        <div class="text-center p-8 bg-white rounded-lg border">
                            <div class="text-3xl font-bold text-gray-900 mb-2">
                                {{ formatPeso(selectedDocumentType.fee_amount) }}
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ !selectedDocumentType.requires_payment ? 'Free Service' : 'Processing Fee' }}
                            </p>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div v-if="selectedDocumentType.required_documents && selectedDocumentType.required_documents.length > 0" class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Required Documents</h3>
                        <ul class="space-y-4">
                            <li v-for="(doc, index) in selectedDocumentType.required_documents" :key="index" class="flex items-start gap-4 p-4 bg-white rounded-lg border">
                                <CheckCircle class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                                <span class="text-sm text-gray-900 pt-0.5">{{ doc }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Processing Steps -->
                    <div v-if="selectedDocumentType.processing_steps && selectedDocumentType.processing_steps.length > 0" class="space-y-6 mb-10 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Processing Steps</h3>
                        <ol class="space-y-4">
                            <li v-for="(step, index) in selectedDocumentType.processing_steps" :key="index" class="flex items-start gap-4 p-4 bg-white rounded-lg border">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ index + 1 }}
                                </div>
                                <span class="text-sm text-gray-900 pt-1">{{ step }}</span>
                            </li>
                        </ol>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 pt-8 mt-10 border-t border-gray-200">
                        <Button
                            variant="outline"
                            @click="closeViewSheet"
                            class="flex-1 h-12 text-gray-700 hover:bg-gray-50"
                        >
                            Close
                        </Button>
                        <Button
                            @click="openEditSheet(selectedDocumentType)"
                            class="flex-1 h-12 bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </StaffLayout>
</template>
