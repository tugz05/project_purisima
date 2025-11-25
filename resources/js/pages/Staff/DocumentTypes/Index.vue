<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Search, Eye, Edit, Trash2, FileText, DollarSign, Clock, CheckCircle, X, FileType, Tag, FileCode } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useUtils } from '@/composables/useUtils';

interface DocumentType {
    id: number;
    code: string;
    name: string;
    description?: string;
    fee_amount: number;
    required_documents?: string[];
    required_fields?: string[];
    processing_steps?: string[];
    processing_days: number;
    is_active: boolean;
    requires_payment: boolean;
    requires_approval: boolean;
    category?: string;
    sort_order: number;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    documentTypes: DocumentType[];
    filters: {
        search?: string;
        active?: boolean;
        category?: string;
    };
}

const props = defineProps<Props>();

// Composables
const { staffDocumentTypesBreadcrumbs } = useBreadcrumbs();
const { formatPeso } = useUtils();

// Breadcrumbs
const breadcrumbs = staffDocumentTypesBreadcrumbs.value;

// Search functionality
const searchQuery = ref(props.filters.search || '');
const filteredDocumentTypes = computed(() => {
    let filtered = props.documentTypes;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(dt =>
            dt.name.toLowerCase().includes(query) ||
            dt.code.toLowerCase().includes(query) ||
            (dt.description && dt.description.toLowerCase().includes(query)) ||
            (dt.category && dt.category.toLowerCase().includes(query))
        );
    }

    return filtered;
});

// Sheet states
const createSheetOpen = ref(false);
const editSheetOpen = ref(false);
const selectedDocumentType = ref<DocumentType | null>(null);

// Dialog states
const deleteDialogOpen = ref(false);
const documentTypeToDelete = ref<DocumentType | null>(null);

// Edit form state
const editForm = ref<any>(null);

// Form for creating document types
const createForm = useForm({
    name: '',
    code: '',
    description: '',
    fee_amount: 0,
    required_documents: [] as string[],
    required_fields: [] as string[],
    processing_steps: [] as string[],
    processing_days: 1,
    is_active: true,
    requires_payment: true,
    requires_approval: false,
    category: null,
    sort_order: 0,
    notes: '',
});

// Required documents management
const newRequiredDoc = ref('');
const newRequiredField = ref('');
const newProcessingStep = ref('');

const addRequiredDoc = () => {
    const value = newRequiredDoc.value.trim();
    if (value) {
        // Ensure array exists
        if (!Array.isArray(createForm.required_documents)) {
            createForm.required_documents = [];
        }
        
        // Check if it contains multiple items (comma or newline separated)
        const hasMultiple = /[,\n]/.test(value);
        
        if (hasMultiple) {
            // Split by comma or newline and add all items
            const items = value.split(/[,\n]/).map(item => {
                const trimmed = item.trim();
                if (!trimmed) return null;
                
                // Try to parse as JSON object
                try {
                    const parsed = JSON.parse(trimmed);
                    if (typeof parsed === 'object' && parsed !== null) {
                        return parsed;
                    }
                } catch {
                    // Not JSON, use as plain string
                }
                return trimmed;
            }).filter(item => item !== null);
            
            if (items.length > 0) {
                createForm.required_documents = [...createForm.required_documents, ...items];
                newRequiredDoc.value = '';
            }
        } else {
            // Single item - try to parse as JSON object first
            let docToAdd: any;
            try {
                const parsed = JSON.parse(value);
                if (typeof parsed === 'object' && parsed !== null) {
                    docToAdd = parsed;
                } else {
                    docToAdd = value;
                }
            } catch {
                // Not JSON, treat as plain string
                docToAdd = value;
            }
            
            createForm.required_documents = [...createForm.required_documents, docToAdd];
            newRequiredDoc.value = '';
        }
    }
};

const removeRequiredDoc = (index: number) => {
    createForm.required_documents.splice(index, 1);
};

// Required fields management
const addRequiredField = () => {
    const value = newRequiredField.value.trim();
    if (value) {
        // Ensure array exists
        if (!Array.isArray(createForm.required_fields)) {
            createForm.required_fields = [];
        }
        
        // Check if it contains multiple items (comma or newline separated)
        const hasMultiple = /[,\n]/.test(value);
        
        if (hasMultiple) {
            // Split by comma or newline and add all items
            const items = value.split(/[,\n]/).map(item => item.trim()).filter(item => item !== '');
            
            if (items.length > 0) {
                createForm.required_fields = [...createForm.required_fields, ...items];
                newRequiredField.value = '';
            }
        } else {
            createForm.required_fields = [...createForm.required_fields, value];
            newRequiredField.value = '';
        }
    }
};

const removeRequiredField = (index: number) => {
    createForm.required_fields.splice(index, 1);
};

const addProcessingStep = () => {
    if (newProcessingStep.value.trim()) {
        createForm.processing_steps.push(newProcessingStep.value.trim());
        newProcessingStep.value = '';
    }
};

const removeProcessingStep = (index: number) => {
    createForm.processing_steps.splice(index, 1);
};

// Edit form helpers
const addEditRequiredDoc = () => {
    const value = newRequiredDoc.value.trim();
    if (value && editForm.value) {
        // Ensure required_documents is an array
        if (!Array.isArray(editForm.value.required_documents)) {
            editForm.value.required_documents = [];
        }
        
        // Check if it contains multiple items (comma or newline separated)
        const hasMultiple = /[,\n]/.test(value);
        
        if (hasMultiple) {
            // Split by comma or newline and add all items
            const items = value.split(/[,\n]/).map(item => {
                const trimmed = item.trim();
                if (!trimmed) return null;
                
                // Try to parse as JSON object
                try {
                    const parsed = JSON.parse(trimmed);
                    if (typeof parsed === 'object' && parsed !== null) {
                        return parsed;
                    }
                } catch {
                    // Not JSON, use as plain string
                }
                return trimmed;
            }).filter(item => item !== null);
            
            if (items.length > 0) {
                editForm.value.required_documents = [...editForm.value.required_documents, ...items];
                newRequiredDoc.value = '';
            }
        } else {
            // Single item - try to parse as JSON object first
            let docToAdd: any;
            try {
                const parsed = JSON.parse(value);
                if (typeof parsed === 'object' && parsed !== null) {
                    docToAdd = parsed;
                } else {
                    docToAdd = value;
                }
            } catch {
                // Not JSON, treat as plain string
                docToAdd = value;
            }
            
            editForm.value.required_documents = [...editForm.value.required_documents, docToAdd];
            newRequiredDoc.value = '';
        }
    }
};

const removeEditRequiredDoc = (index: number) => {
    if (editForm.value && Array.isArray(editForm.value.required_documents)) {
        const updated = [...editForm.value.required_documents];
        updated.splice(index, 1);
        editForm.value.required_documents = updated;
    }
};

// Edit required fields management
const addEditRequiredField = () => {
    const value = newRequiredField.value.trim();
    if (value && editForm.value) {
        // Ensure required_fields is an array
        if (!Array.isArray(editForm.value.required_fields)) {
            editForm.value.required_fields = [];
        }
        
        // Check if it contains multiple items (comma or newline separated)
        const hasMultiple = /[,\n]/.test(value);
        
        if (hasMultiple) {
            // Split by comma or newline and add all items
            const items = value.split(/[,\n]/).map(item => item.trim()).filter(item => item !== '');
            
            if (items.length > 0) {
                editForm.value.required_fields = [...editForm.value.required_fields, ...items];
                newRequiredField.value = '';
            }
        } else {
            editForm.value.required_fields = [...editForm.value.required_fields, value];
            newRequiredField.value = '';
        }
    }
};

const removeEditRequiredField = (index: number) => {
    if (editForm.value && Array.isArray(editForm.value.required_fields)) {
        const updated = [...editForm.value.required_fields];
        updated.splice(index, 1);
        editForm.value.required_fields = updated;
    }
};

const addEditProcessingStep = () => {
    if (newProcessingStep.value.trim() && editForm.value) {
        // Ensure processing_steps is an array
        if (!Array.isArray(editForm.value.processing_steps)) {
            editForm.value.processing_steps = [];
        }
        editForm.value.processing_steps = [...editForm.value.processing_steps, newProcessingStep.value.trim()];
        newProcessingStep.value = '';
    }
};

const removeEditProcessingStep = (index: number) => {
    if (editForm.value && Array.isArray(editForm.value.processing_steps)) {
        const updated = [...editForm.value.processing_steps];
        updated.splice(index, 1);
        editForm.value.processing_steps = updated;
    }
};

// Sheet functions
const openCreateSheet = () => {
    createSheetOpen.value = true;
    resetCreateForm();
};

// Helper function to properly convert boolean values (handles 1/0 from database)
const toBoolean = (value: any): boolean => {
    // Handle null/undefined
    if (value === null || value === undefined) return false;
    
    // Already a boolean
    if (typeof value === 'boolean') return value;
    
    // Number values (0 = false, 1 = true) - PRIMARY CASE for database values
    if (typeof value === 'number') {
        return value === 1;
    }
    
    // String values (could be "1", "0", "true", "false")
    if (typeof value === 'string') {
        const trimmed = value.trim();
        // Check for numeric strings first
        if (trimmed === '1' || trimmed === '0') {
            return trimmed === '1';
        }
        // Then check for boolean strings
        const lower = trimmed.toLowerCase();
        return lower === 'true' || lower === 'yes' || lower === 'on';
    }
    
    // Default: truthy check (but 0 should be false)
    return value !== 0 && Boolean(value);
};

// Refs for checkbox values - these will be synced with form
const editIsActiveChecked = ref(false);
const editRequiresPaymentChecked = ref(false);
const editRequiresApprovalChecked = ref(false);

// Watch for checkbox changes and sync to form
watch(editIsActiveChecked, (val) => {
    if (editForm.value) {
        editForm.value.is_active = val;
    }
});

watch(editRequiresPaymentChecked, (val) => {
    if (editForm.value) {
        editForm.value.requires_payment = val;
    }
});

watch(editRequiresApprovalChecked, (val) => {
    if (editForm.value) {
        editForm.value.requires_approval = val;
    }
});

// Helper function to parse JSON strings or return array (handles both strings and objects)
const parseArrayField = (value: any): any[] => {
    if (!value) return [];
    if (Array.isArray(value)) {
        // Ensure each item is properly parsed if it's a JSON string
        return value.map(item => {
            if (typeof item === 'string') {
                try {
                    return JSON.parse(item);
                } catch {
                    return item;
                }
            }
            return item;
        });
    }
    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);
            if (Array.isArray(parsed)) {
                // Parse nested JSON strings within the array
                return parsed.map(item => {
                    if (typeof item === 'string') {
                        try {
                            return JSON.parse(item);
                        } catch {
                            return item;
                        }
                    }
                    return item;
                });
            }
            return [];
        } catch {
            return [];
        }
    }
    return [];
};

const openEditSheet = (documentType: DocumentType) => {
    selectedDocumentType.value = documentType;

    // DEBUG: Log the raw documentType object
    console.log('=== DEBUG: DocumentType received ===');
    console.log('Full documentType object:', documentType);
    console.log('Raw values:', {
        is_active: documentType.is_active,
        requires_payment: documentType.requires_payment,
        requires_approval: documentType.requires_approval,
    });
    console.log('Value types:', {
        is_active: typeof documentType.is_active,
        requires_payment: typeof documentType.requires_payment,
        requires_approval: typeof documentType.requires_approval,
    });
    console.log('Value equality checks:', {
        'is_active === 1': documentType.is_active === 1,
        'is_active === true': documentType.is_active === true,
        'requires_payment === 1': documentType.requires_payment === 1,
        'requires_payment === true': documentType.requires_payment === true,
        'requires_approval === 0': documentType.requires_approval === 0,
        'requires_approval === false': documentType.requires_approval === false,
    });

    // Parse arrays properly - handle JSON strings, arrays, or null
    const requiredDocs = parseArrayField(documentType.required_documents);
    const requiredFields = parseArrayField(documentType.required_fields);
    const processingSteps = parseArrayField(documentType.processing_steps);
    
    // If no processing steps exist, use defaults
    const finalProcessingSteps = processingSteps.length > 0 
        ? processingSteps 
        : getDefaultProcessingSteps();

    // Convert 1/0 from database to proper booleans
    const isActive = toBoolean(documentType.is_active);
    const requiresPayment = toBoolean(documentType.requires_payment);
    const requiresApproval = toBoolean(documentType.requires_approval);

    console.log('After toBoolean conversion:', {
        isActive,
        requiresPayment,
        requiresApproval,
        types: {
            isActive: typeof isActive,
            requiresPayment: typeof requiresPayment,
            requiresApproval: typeof requiresApproval,
        }
    });

    // Initialize edit form with document type data
    // CRITICAL: Ensure values are explicitly booleans, not 1/0
    editForm.value = useForm({
        name: documentType.name || '',
        code: documentType.code || '', // Hidden field, auto-generated
        description: documentType.description || '',
        fee_amount: Number(documentType.fee_amount) || 0,
        required_documents: requiredDocs,
        required_fields: requiredFields,
        processing_steps: finalProcessingSteps,
        processing_days: Number(documentType.processing_days) || 1,
        is_active: Boolean(isActive),
        requires_payment: Boolean(requiresPayment),
        requires_approval: Boolean(requiresApproval),
        category: documentType.category || null,
        sort_order: Number(documentType.sort_order) || 0, // Hidden field
        notes: documentType.notes || '',
    });

    console.log('Form initialized:', {
        form_is_active: editForm.value.is_active,
        form_requires_payment: editForm.value.requires_payment,
        form_requires_approval: editForm.value.requires_approval,
        form_types: {
            is_active: typeof editForm.value.is_active,
            requires_payment: typeof editForm.value.requires_payment,
            requires_approval: typeof editForm.value.requires_approval,
        }
    });

    // Set checkbox refs BEFORE opening sheet - this ensures they have correct values when rendered
    editIsActiveChecked.value = Boolean(isActive);
    editRequiresPaymentChecked.value = Boolean(requiresPayment);
    editRequiresApprovalChecked.value = Boolean(requiresApproval);
    
    console.log('Checkbox refs set BEFORE sheet open:', {
        editIsActiveChecked: editIsActiveChecked.value,
        editRequiresPaymentChecked: editRequiresPaymentChecked.value,
        editRequiresApprovalChecked: editRequiresApprovalChecked.value,
        types: {
            editIsActiveChecked: typeof editIsActiveChecked.value,
            editRequiresPaymentChecked: typeof editRequiresPaymentChecked.value,
            editRequiresApprovalChecked: typeof editRequiresApprovalChecked.value,
        }
    });

    // Clear input fields
    newRequiredDoc.value = '';
    newProcessingStep.value = '';

    // Open sheet AFTER refs are set
    editSheetOpen.value = true;
    
    console.log('=== END DEBUG ===');
};

// Default processing steps for all document types
const getDefaultProcessingSteps = (): string[] => {
    return [
        'Submit required documents',
        'Verification and review',
        'Approval process',
        'Document issuance'
    ];
};

const resetCreateForm = () => {
    createForm.reset();
    createForm.required_documents = [];
    createForm.required_fields = [];
    createForm.processing_steps = getDefaultProcessingSteps(); // Set default steps
    createForm.is_active = true;
    createForm.requires_payment = true;
    createForm.requires_approval = false;
    createForm.processing_days = 1;
    createForm.fee_amount = 0;
    createForm.sort_order = 0; // Hidden field, auto-set
    createForm.code = ''; // Hidden field, auto-generated
    newRequiredDoc.value = '';
    newProcessingStep.value = '';
};

const submitCreate = () => {
    // Ensure arrays are properly formatted before submission
    if (!Array.isArray(createForm.required_documents)) {
        createForm.required_documents = [];
    }
    if (!Array.isArray(createForm.processing_steps)) {
        createForm.processing_steps = [];
    }

    // Filter out empty strings
    createForm.required_documents = createForm.required_documents.filter((doc: any) => doc && String(doc).trim() !== '');
    createForm.required_fields = createForm.required_fields.filter((field: any) => field && String(field).trim() !== '');
    createForm.processing_steps = createForm.processing_steps.filter((step: any) => step && String(step).trim() !== '');

    // Convert null category to empty string for backend
    if (createForm.category === null) {
        createForm.category = '';
    }

    createForm.post('/staff/document-types', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`${createForm.name} has been created successfully.`);
            createSheetOpen.value = false;
            resetCreateForm();
            // Reload the page data using Inertia
            router.reload({ only: ['documentTypes'] });
        },
        onError: (errors) => {
            console.error('Create errors:', errors);
            toast.error('Failed to create document type. Please check the form for errors.');
        },
        onFinish: () => {
            createForm.clearErrors();
        }
    });
};

const submitEdit = () => {
    if (editForm.value && selectedDocumentType.value) {
        // Ensure arrays are properly formatted before submission
        if (!Array.isArray(editForm.value.required_documents)) {
            editForm.value.required_documents = [];
        }
        if (!Array.isArray(editForm.value.processing_steps)) {
            editForm.value.processing_steps = [];
        }

        // Filter out empty strings
        editForm.value.required_documents = editForm.value.required_documents.filter((doc: any) => doc && String(doc).trim() !== '');
        editForm.value.required_fields = editForm.value.required_fields.filter((field: any) => field && String(field).trim() !== '');
        editForm.value.processing_steps = editForm.value.processing_steps.filter((step: any) => step && String(step).trim() !== '');

        // Convert null category to empty string for backend
        if (editForm.value.category === null) {
            editForm.value.category = '';
        }

        editForm.value.put(`/staff/document-types/${selectedDocumentType.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`${editForm.value?.name || 'Document type'} has been updated successfully.`);
                editSheetOpen.value = false;
                editForm.value = null;
                selectedDocumentType.value = null;
                // Reload the page data using Inertia
                router.reload({ only: ['documentTypes'] });
            },
            onError: (errors: any) => {
                console.error('Edit errors:', errors);
                toast.error('Failed to update document type. Please check the form for errors.');
            },
            onFinish: () => {
                // Ensure form is not frozen
                if (editForm.value) {
                    editForm.value.clearErrors();
                }
            }
        });
    }
};

// Delete confirmation
const openDeleteDialog = (documentType: DocumentType) => {
    documentTypeToDelete.value = documentType;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (documentTypeToDelete.value) {
        const deleteForm = useForm({});
        deleteForm.delete(`/staff/document-types/${documentTypeToDelete.value.id}`, {
            onSuccess: () => {
                toast.success(`${documentTypeToDelete.value?.name} has been deleted successfully.`);
                deleteDialogOpen.value = false;
                documentTypeToDelete.value = null;
            },
            onError: (errors) => {
                toast.error(errors.message || 'Failed to delete document type. Please try again.');
            }
        });
    }
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    documentTypeToDelete.value = null;
};

const getStatusColor = (isActive: boolean) => {
    return isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
};

const getCategoryColor = (category?: string) => {
    const categoryColors: Record<string, string> = {
        'certificates': 'bg-blue-100 text-blue-800',
        'permits': 'bg-purple-100 text-purple-800',
        'taxes': 'bg-green-100 text-green-800',
        'other': 'bg-gray-100 text-gray-800',
    };

    return categoryColors[category || 'other'] || 'bg-gray-100 text-gray-800';
};

// Category options
const categoryOptions = [
    'certificates',
    'permits',
    'taxes',
    'other'
];
</script>

<template>
    <Head title="Document Types" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-full">
                <!-- Enhanced Header with Gradient -->
                <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-emerald-600 to-green-600 shadow-xl mb-6 rounded-2xl">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute inset-0">
                        <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full -translate-x-36 -translate-y-36"></div>
                        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-48 translate-y-48"></div>
                    </div>
                    <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                            <div class="text-white">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                        <FileType class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h1 class="text-4xl font-bold">Document Types</h1>
                                        <p class="text-teal-100 text-lg mt-1">Manage different types of barangay documents</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-sm text-teal-100">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-teal-300 rounded-full"></div>
                                        <span>{{ props.documentTypes.length }} Total Types</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-green-300 rounded-full"></div>
                                        <span>{{ props.documentTypes.filter(dt => dt.is_active).length }} Active</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-emerald-300 rounded-full"></div>
                                        <span>{{ props.documentTypes.filter(dt => dt.requires_payment).length }} Require Payment</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-teal-300 rounded-full"></div>
                                        <span>{{ new Set(props.documentTypes.map(dt => dt.category).filter(Boolean)).size }} Categories</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <Button @click="openCreateSheet" class="bg-white text-teal-600 hover:bg-teal-50 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                    <Plus class="w-5 h-5 mr-2" />
                                    New Document Type
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between mb-6">
                            <!-- Search -->
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search document types..."
                                        class="pl-10 h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Add Button -->
                            <Button @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white font-medium h-10 px-6 shadow-lg">
                                <Plus class="h-4 w-4 mr-2" />
                                Add New Document Type
                            </Button>
                        </div>

                <!-- Document Types List -->
                <div v-if="filteredDocumentTypes.length > 0" class="space-y-4">
                    <!-- List Header -->
                    <div class="flex items-center justify-between py-2 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Document Types
                            <span class="text-sm font-normal text-gray-500 ml-2">({{ filteredDocumentTypes.length }} {{ filteredDocumentTypes.length === 1 ? 'type' : 'types' }})</span>
                        </h2>
                    </div>

                    <!-- Document Types Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <Card v-for="documentType in filteredDocumentTypes" :key="documentType.id" class="group shadow-sm border-gray-200 hover:shadow-lg hover:border-blue-300 transition-all duration-200 flex flex-col h-full">
                            <CardContent class="p-4 flex flex-col h-full">
                                <!-- Document Type Header -->
                                <div class="mb-4 flex-shrink-0">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 min-h-[3.5rem]">
                                            {{ documentType.name }}
                                        </h3>
                                        <Badge :class="getStatusColor(documentType.is_active)" class="text-xs font-medium flex-shrink-0 ml-2">
                                            {{ documentType.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <Badge variant="outline" class="text-xs">
                                            {{ documentType.code }}
                                        </Badge>
                                        <Badge v-if="documentType.category" :class="getCategoryColor(documentType.category)" class="text-xs">
                                            {{ documentType.category }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Description - Fixed Height -->
                                <div class="mb-4 flex-shrink-0 min-h-[3rem]">
                                    <p v-if="documentType.description" class="text-sm text-gray-600 line-clamp-2">
                                        {{ documentType.description }}
                                    </p>
                                    <p v-else class="text-sm text-gray-400 italic">
                                        No description provided
                                    </p>
                                </div>

                                <!-- Details - Fixed Height -->
                                <div class="space-y-2 mb-4 flex-shrink-0 min-h-[5rem]">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <DollarSign class="h-4 w-4 text-blue-600 flex-shrink-0" />
                                        <span>Fee: <strong>{{ formatPeso(documentType.fee_amount) }}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <Clock class="h-4 w-4 text-green-600 flex-shrink-0" />
                                        <span>Processing: <strong>{{ documentType.processing_days }} day{{ documentType.processing_days !== 1 ? 's' : '' }}</strong></span>
                                    </div>
                                    <div v-if="documentType.required_documents && documentType.required_documents.length > 0" class="text-sm text-gray-600">
                                        <strong>Required Documents:</strong> {{ documentType.required_documents.length }}
                                    </div>
                                    <div v-else class="text-sm text-gray-400">
                                        <strong>Required Documents:</strong> None
                                    </div>
                                </div>

                                <!-- Flags - Fixed Height -->
                                <div class="flex flex-wrap gap-2 mb-4 flex-shrink-0 min-h-[1.75rem]">
                                    <Badge v-if="documentType.requires_payment" variant="outline" class="text-xs">
                                        Payment Required
                                    </Badge>
                                    <Badge v-if="documentType.requires_approval" variant="outline" class="text-xs">
                                        Approval Required
                                    </Badge>
                                    <Badge v-if="documentType.fee_amount === 0" variant="outline" class="text-xs text-green-600">
                                        Free
                                    </Badge>
                                    <span v-if="!documentType.requires_payment && !documentType.requires_approval && documentType.fee_amount !== 0" class="text-xs text-gray-400 italic">
                                        No special requirements
                                    </span>
                                </div>

                                <!-- Quick Actions - Always at bottom -->
                                <div class="flex flex-col gap-2 pt-2 border-t border-gray-100 mt-auto flex-shrink-0">
                                    <Button
                                        @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates`)"
                                        variant="outline"
                                        size="sm"
                                        class="w-full h-8 text-blue-600 border-blue-200 hover:bg-blue-50"
                                    >
                                        <FileCode class="h-3 w-3 mr-1" />
                                        Templates
                                    </Button>
                                    <div class="flex gap-2">
                                        <Button @click="openEditSheet(documentType)" variant="outline" size="sm" class="flex-1 h-8 text-green-600 border-green-200 hover:bg-green-50">
                                            <Edit class="h-3 w-3 mr-1" />
                                            Edit
                                        </Button>
                                        <Button
                                            @click="openDeleteDialog(documentType)"
                                            variant="outline"
                                            size="sm"
                                            class="h-8 px-3 text-red-600 border-red-200 hover:bg-red-50"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <FileType class="h-10 w-10 text-blue-600" />
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 mb-3">
                            {{ searchQuery ? 'No document types found' : 'No document types yet' }}
                        </h3>

                        <p class="text-gray-600 mb-8 leading-relaxed">
                            {{ searchQuery
                                ? 'Try adjusting your search terms or clear the search to see all document types.'
                                : 'Start by adding your first document type (e.g., Barangay Permit, Barangay Certificate).'
                            }}
                        </p>

                        <div class="space-y-4">
                            <Button v-if="!searchQuery" @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 shadow-lg">
                                <Plus class="h-5 w-5 mr-2" />
                                Add Your First Document Type
                            </Button>

                            <div v-if="searchQuery" class="flex gap-3 justify-center">
                                <Button @click="searchQuery = ''" variant="outline" class="px-6 py-2">
                                    Clear Search
                                </Button>
                                <Button @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Document Type
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Document Type Sheet -->
        <Sheet :open="createSheetOpen" @update:open="createSheetOpen = $event">
            <SheetContent class="p-0 flex flex-col h-full overflow-y-auto">
                <SheetHeader class="p-6 pb-4 border-b border-gray-200">
                    <SheetTitle class="flex items-center gap-2 text-xl font-semibold">
                        <Plus class="h-5 w-5 text-blue-600" />
                        Create Document Type
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Add a new document type to the system
                    </SheetDescription>
                </SheetHeader>

                <form @submit.prevent="submitCreate" class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Basic Information -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-blue-600" />
                            Basic Information
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <Label for="name" class="text-sm font-medium text-gray-700">Name *</Label>
                                <Input
                                    id="name"
                                    v-model="createForm.name"
                                    placeholder="e.g., Barangay Permit, Barangay Certificate"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': createForm.errors.name }"
                                />
                                <p v-if="createForm.errors.name" class="text-red-500 text-xs mt-1">{{ createForm.errors.name }}</p>
                            </div>

                            <div>
                                <Label for="description" class="text-sm font-medium text-gray-700">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="createForm.description"
                                    placeholder="Describe what this document type is for..."
                                    rows="3"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <Label for="category" class="text-sm font-medium text-gray-700">Category</Label>
                                <Select v-model="createForm.category">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">None</SelectItem>
                                        <SelectItem v-for="cat in categoryOptions" :key="cat" :value="cat">
                                            {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <!-- Fee & Processing -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <DollarSign class="h-5 w-5 text-green-600" />
                            Fee & Processing
                        </h3>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label for="fee_amount" class="text-sm font-medium text-gray-700">Fee Amount *</Label>
                                    <Input
                                        id="fee_amount"
                                        v-model.number="createForm.fee_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                        :class="{ 'border-red-500': createForm.errors.fee_amount }"
                                    />
                                    <p v-if="createForm.errors.fee_amount" class="text-red-500 text-xs mt-1">{{ createForm.errors.fee_amount }}</p>
                                </div>

                                <div>
                                    <Label for="processing_days" class="text-sm font-medium text-gray-700">Processing Days *</Label>
                                    <Input
                                        id="processing_days"
                                        v-model.number="createForm.processing_days"
                                        type="number"
                                        min="1"
                                        class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                        :class="{ 'border-red-500': createForm.errors.processing_days }"
                                    />
                                    <p v-if="createForm.errors.processing_days" class="text-red-500 text-xs mt-1">{{ createForm.errors.processing_days }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox"
                                        id="requires_payment" 
                                        v-model="createForm.requires_payment"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    />
                                    <Label for="requires_payment" class="text-sm font-medium text-gray-700 cursor-pointer">Requires Payment</Label>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox"
                                        id="requires_approval" 
                                        v-model="createForm.requires_approval"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    />
                                    <Label for="requires_approval" class="text-sm font-medium text-gray-700 cursor-pointer">Requires Approval</Label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-purple-600" />
                            Required Documents
                        </h3>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newRequiredDoc"
                                        placeholder="Enter document name(s) - Use commas or new lines for multiple"
                                        class="flex-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                                        @keyup.enter="addRequiredDoc"
                                    />
                                    <Button type="button" @click="addRequiredDoc" variant="outline" size="sm" class="px-4">
                                        <Plus class="h-4 w-4 mr-1" />
                                        Add
                                    </Button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    💡 Tip: Separate multiple documents with commas or new lines (e.g., "Valid ID, Proof of Residency, Utility Bill")
                                </p>
                            </div>

                            <div v-if="Array.isArray(createForm.required_documents) && createForm.required_documents.length > 0" class="space-y-2">
                                <div v-for="(doc, index) in createForm.required_documents" :key="`create-doc-${index}`" class="flex items-start gap-2 p-3 bg-white rounded border border-gray-200 hover:border-purple-300 transition-colors">
                                    <div class="flex-1">
                                        <div v-if="typeof doc === 'object' && doc !== null" class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-sm text-gray-900">{{ doc.label || doc.name || 'Unnamed Field' }}</span>
                                                <Badge v-if="doc.required" variant="outline" class="text-xs text-red-600 border-red-300">Required</Badge>
                                                <Badge v-else variant="outline" class="text-xs text-gray-500">Optional</Badge>
                                            </div>
                                            <div class="flex flex-wrap gap-2 text-xs text-gray-600">
                                                <span class="px-2 py-0.5 bg-gray-100 rounded">Type: {{ doc.type || 'text' }}</span>
                                                <span v-if="doc.name" class="px-2 py-0.5 bg-gray-100 rounded">Name: {{ doc.name }}</span>
                                                <span v-if="doc.options" class="px-2 py-0.5 bg-gray-100 rounded">Options: {{ Array.isArray(doc.options) ? doc.options.join(', ') : doc.options }}</span>
                                                <span v-if="doc.default" class="px-2 py-0.5 bg-gray-100 rounded">Default: {{ doc.default }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-900">{{ typeof doc === 'string' ? doc : JSON.stringify(doc) }}</div>
                                    </div>
                                    <Button type="button" @click="removeRequiredDoc(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600 hover:bg-red-50 flex-shrink-0">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Required Fields -->
                    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Tag class="h-5 w-5 text-cyan-600" />
                            Required Fields
                        </h3>
                        <p class="text-xs text-gray-600 mb-4">These are text fields that residents must fill in when requesting this document type.</p>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newRequiredField"
                                        placeholder="Enter field name(s) - Use commas or new lines for multiple"
                                        class="flex-1 border-gray-200 focus:border-cyan-500 focus:ring-cyan-500"
                                        @keyup.enter="addRequiredField"
                                    />
                                    <Button type="button" @click="addRequiredField" variant="outline" size="sm" class="px-4">
                                        <Plus class="h-4 w-4 mr-1" />
                                        Add
                                    </Button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    💡 Tip: Examples: "Purpose", "Business Name", "Contact Number", etc.
                                </p>
                            </div>

                            <div v-if="Array.isArray(createForm.required_fields) && createForm.required_fields.length > 0" class="space-y-2">
                                <div v-for="(field, index) in createForm.required_fields" :key="`create-field-${index}`" class="flex items-center gap-2 p-3 bg-white rounded border border-gray-200 hover:border-cyan-300 transition-colors">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-900 font-medium">{{ field }}</span>
                                    </div>
                                    <Button type="button" @click="removeRequiredField(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600 hover:bg-red-50 flex-shrink-0">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Processing Steps -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Clock class="h-5 w-5 text-orange-600" />
                            Processing Steps
                        </h3>
                        <p class="text-xs text-gray-600 mb-4">Default processing steps are pre-filled. You can modify them if needed.</p>

                        <div class="space-y-4">
                            <div class="flex gap-2">
                                <Input
                                    v-model="newProcessingStep"
                                    placeholder="Add custom processing step (optional)"
                                    class="flex-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                    @keyup.enter="addProcessingStep"
                                />
                                <Button type="button" @click="addProcessingStep" variant="outline" size="sm">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>

                            <div v-if="Array.isArray(createForm.processing_steps) && createForm.processing_steps.length > 0" class="space-y-2">
                                <div v-for="(step, index) in createForm.processing_steps" :key="`create-step-${index}`" class="flex items-center gap-2 p-2 bg-white rounded border border-gray-200">
                                    <span class="flex-1 text-sm">{{ index + 1 }}. {{ typeof step === 'string' ? step : JSON.stringify(step) }}</span>
                                    <Button type="button" @click="removeProcessingStep(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-gray-600" />
                            Internal Notes
                        </h3>

                        <div>
                            <Label for="notes" class="text-sm font-medium text-gray-700">Notes</Label>
                            <Textarea
                                id="notes"
                                v-model="createForm.notes"
                                placeholder="Internal notes for staff..."
                                rows="3"
                                class="mt-1 border-gray-200 focus:border-gray-500 focus:ring-gray-500"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <div class="flex items-center space-x-2">
                            <input 
                                type="checkbox"
                                id="is_active" 
                                v-model="createForm.is_active"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            />
                            <Label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active Document Type</Label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200">
                        <Button type="button" variant="outline" @click="createSheetOpen = false" class="w-full sm:w-auto">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium h-10 px-6"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create Document Type' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Edit Document Type Sheet -->
        <Sheet :key="`edit-${selectedDocumentType?.id}`" :open="editSheetOpen" @update:open="(val) => { editSheetOpen = val; if (!val) { editIsActiveChecked = false; editRequiresPaymentChecked = false; editRequiresApprovalChecked = false; } }">
            <SheetContent class="p-0 flex flex-col h-full overflow-y-auto">
                <SheetHeader class="p-6 pb-4 border-b border-gray-200">
                    <SheetTitle class="flex items-center gap-2 text-xl font-semibold">
                        <Edit class="h-5 w-5 text-green-600" />
                        Edit {{ selectedDocumentType?.name }}
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Update the document type information
                    </SheetDescription>
                </SheetHeader>

                <form v-if="editForm" @submit.prevent="submitEdit" class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Basic Information -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-green-600" />
                            Basic Information
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <Label for="edit_name" class="text-sm font-medium text-gray-700">Name *</Label>
                                <Input
                                    id="edit_name"
                                    v-model="editForm.name"
                                    placeholder="e.g., Barangay Permit, Barangay Certificate"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                    :class="{ 'border-red-500': editForm.errors.name }"
                                />
                                <p v-if="editForm.errors.name" class="text-red-500 text-xs mt-1">{{ editForm.errors.name }}</p>
                            </div>

                            <div>
                                <Label for="edit_description" class="text-sm font-medium text-gray-700">Description</Label>
                                <Textarea
                                    id="edit_description"
                                    v-model="editForm.description"
                                    placeholder="Describe what this document type is for..."
                                    rows="3"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label for="edit_category" class="text-sm font-medium text-gray-700">Category</Label>
                                <Select v-model="editForm.category">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">None</SelectItem>
                                        <SelectItem v-for="cat in categoryOptions" :key="cat" :value="cat">
                                            {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fee & Processing -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <DollarSign class="h-5 w-5 text-blue-600" />
                            Fee & Processing
                        </h3>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label for="edit_fee_amount" class="text-sm font-medium text-gray-700">Fee Amount *</Label>
                                    <Input
                                        id="edit_fee_amount"
                                        v-model.number="editForm.fee_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': editForm.errors.fee_amount }"
                                    />
                                    <p v-if="editForm.errors.fee_amount" class="text-red-500 text-xs mt-1">{{ editForm.errors.fee_amount }}</p>
                                </div>

                                <div>
                                    <Label for="edit_processing_days" class="text-sm font-medium text-gray-700">Processing Days *</Label>
                                    <Input
                                        id="edit_processing_days"
                                        v-model.number="editForm.processing_days"
                                        type="number"
                                        min="1"
                                        class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': editForm.errors.processing_days }"
                                    />
                                    <p v-if="editForm.errors.processing_days" class="text-red-500 text-xs mt-1">{{ editForm.errors.processing_days }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox"
                                        id="edit_requires_payment" 
                                        :checked="editRequiresPaymentChecked"
                                        @change.stop="editRequiresPaymentChecked = ($event.target as HTMLInputElement).checked"
                                        @click.stop
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    />
                                    <Label for="edit_requires_payment" class="text-sm font-medium text-gray-700 cursor-pointer">Requires Payment</Label>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox"
                                        id="edit_requires_approval" 
                                        :checked="editRequiresApprovalChecked"
                                        @change.stop="editRequiresApprovalChecked = ($event.target as HTMLInputElement).checked"
                                        @click.stop
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    />
                                    <Label for="edit_requires_approval" class="text-sm font-medium text-gray-700 cursor-pointer">Requires Approval</Label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-purple-600" />
                            Required Documents
                        </h3>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newRequiredDoc"
                                        placeholder="Enter document name(s) - Use commas or new lines for multiple"
                                        class="flex-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                                        @keyup.enter="addEditRequiredDoc"
                                    />
                                    <Button type="button" @click="addEditRequiredDoc" variant="outline" size="sm" class="px-4">
                                        <Plus class="h-4 w-4 mr-1" />
                                        Add
                                    </Button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    💡 Tip: Separate multiple documents with commas or new lines (e.g., "Valid ID, Proof of Residency, Utility Bill")
                                </p>
                            </div>

                            <div v-if="editForm && Array.isArray(editForm.required_documents) && editForm.required_documents.length > 0" class="space-y-2">
                                <div v-for="(doc, index) in editForm.required_documents" :key="`edit-doc-${index}`" class="flex items-start gap-2 p-3 bg-white rounded border border-gray-200 hover:border-purple-300 transition-colors">
                                    <div class="flex-1">
                                        <div v-if="typeof doc === 'object' && doc !== null" class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-sm text-gray-900">{{ doc.label || doc.name || 'Unnamed Field' }}</span>
                                                <Badge v-if="doc.required" variant="outline" class="text-xs text-red-600 border-red-300">Required</Badge>
                                                <Badge v-else variant="outline" class="text-xs text-gray-500">Optional</Badge>
                                            </div>
                                            <div class="flex flex-wrap gap-2 text-xs text-gray-600">
                                                <span class="px-2 py-0.5 bg-gray-100 rounded">Type: {{ doc.type || 'text' }}</span>
                                                <span v-if="doc.name" class="px-2 py-0.5 bg-gray-100 rounded">Name: {{ doc.name }}</span>
                                                <span v-if="doc.options" class="px-2 py-0.5 bg-gray-100 rounded">Options: {{ Array.isArray(doc.options) ? doc.options.join(', ') : doc.options }}</span>
                                                <span v-if="doc.default" class="px-2 py-0.5 bg-gray-100 rounded">Default: {{ doc.default }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-900">{{ typeof doc === 'string' ? doc : JSON.stringify(doc) }}</div>
                                    </div>
                                    <Button type="button" @click="removeEditRequiredDoc(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600 hover:bg-red-50 flex-shrink-0">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div v-else-if="editForm && (!Array.isArray(editForm.required_documents) || editForm.required_documents.length === 0)" class="text-sm text-gray-500 italic">
                                No required documents added yet
                            </div>
                        </div>
                    </div>

                    <!-- Required Fields -->
                    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Tag class="h-5 w-5 text-cyan-600" />
                            Required Fields
                        </h3>
                        <p class="text-xs text-gray-600 mb-4">These are text fields that residents must fill in when requesting this document type.</p>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="newRequiredField"
                                        placeholder="Enter field name(s) - Use commas or new lines for multiple"
                                        class="flex-1 border-gray-200 focus:border-cyan-500 focus:ring-cyan-500"
                                        @keyup.enter="addEditRequiredField"
                                    />
                                    <Button type="button" @click="addEditRequiredField" variant="outline" size="sm" class="px-4">
                                        <Plus class="h-4 w-4 mr-1" />
                                        Add
                                    </Button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    💡 Tip: Examples: "Purpose", "Business Name", "Contact Number", etc.
                                </p>
                            </div>

                            <div v-if="editForm && Array.isArray(editForm.required_fields) && editForm.required_fields.length > 0" class="space-y-2">
                                <div v-for="(field, index) in editForm.required_fields" :key="`edit-field-${index}`" class="flex items-center gap-2 p-3 bg-white rounded border border-gray-200 hover:border-cyan-300 transition-colors">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-900 font-medium">{{ field }}</span>
                                    </div>
                                    <Button type="button" @click="removeEditRequiredField(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600 hover:bg-red-50 flex-shrink-0">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div v-else-if="editForm && (!Array.isArray(editForm.required_fields) || editForm.required_fields.length === 0)" class="text-sm text-gray-500 italic">
                                No required fields added yet
                            </div>
                        </div>
                    </div>

                    <!-- Processing Steps -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Clock class="h-5 w-5 text-orange-600" />
                            Processing Steps
                        </h3>
                        <p class="text-xs text-gray-600 mb-4">Default processing steps are pre-filled. You can modify them if needed.</p>

                        <div class="space-y-4">
                            <div class="flex gap-2">
                                <Input
                                    v-model="newProcessingStep"
                                    placeholder="Add custom processing step (optional)"
                                    class="flex-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                    @keyup.enter="addEditProcessingStep"
                                />
                                <Button type="button" @click="addEditProcessingStep" variant="outline" size="sm">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>

                            <div v-if="editForm && Array.isArray(editForm.processing_steps) && editForm.processing_steps.length > 0" class="space-y-2">
                                <div v-for="(step, index) in editForm.processing_steps" :key="`edit-step-${index}`" class="flex items-center gap-2 p-2 bg-white rounded border border-gray-200">
                                    <span class="flex-1 text-sm">{{ index + 1 }}. {{ typeof step === 'string' ? step : JSON.stringify(step) }}</span>
                                    <Button type="button" @click="removeEditProcessingStep(index)" variant="ghost" size="sm" class="h-6 w-6 p-0 text-red-600">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div v-else-if="editForm && (!Array.isArray(editForm.processing_steps) || editForm.processing_steps.length === 0)" class="text-sm text-gray-500 italic">
                                No processing steps added yet
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-gray-600" />
                            Internal Notes
                        </h3>

                        <div>
                            <Label for="edit_notes" class="text-sm font-medium text-gray-700">Notes</Label>
                            <Textarea
                                id="edit_notes"
                                v-model="editForm.notes"
                                placeholder="Internal notes for staff..."
                                rows="3"
                                class="mt-1 border-gray-200 focus:border-gray-500 focus:ring-gray-500"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                        <div class="flex items-center space-x-2">
                            <input 
                                type="checkbox"
                                id="edit_is_active" 
                                :checked="editIsActiveChecked"
                                @change.stop="editIsActiveChecked = ($event.target as HTMLInputElement).checked"
                                @click.stop
                                class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500 cursor-pointer"
                            />
                            <Label for="edit_is_active" class="text-sm font-medium text-gray-700 cursor-pointer">Active Document Type</Label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200">
                        <Button type="button" variant="outline" @click="editSheetOpen = false" class="w-full sm:w-auto">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-medium h-10 px-6"
                        >
                            {{ editForm.processing ? 'Updating...' : 'Update Document Type' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-red-600" />
                        Delete Document Type
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ documentTypeToDelete?.name }}</strong>?
                        This action cannot be undone. Document types with associated transactions cannot be deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="flex gap-3">
                    <Button variant="outline" @click="cancelDelete">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </StaffLayout>
</template>

