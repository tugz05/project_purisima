<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
    ArrowLeft,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    User,
    FileText,
    Calendar,
    DollarSign,
    Download,
    File,
    Eye,
    X,
    ZoomIn,
    ZoomOut,
    RotateCcw,
    Maximize2,
    Minimize2,
    Save,
    Sparkles,
    FileCheck,
    Printer,
    Image,
    FileSpreadsheet,
    Folder,
    Move,
    AlertTriangle,
    HelpCircle,
    ClipboardList,
    type LucideIcon,
} from 'lucide-vue-next';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import RichTextEditor from '@/components/RichTextEditor.vue';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useUtils } from '@/composables/useUtils';
import { Link } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description: string;
    status: string;
    fee_amount: number;
    fee_paid: boolean;
    payment_status: string;
    payment_method: string;
    amount_paid: number;
    payment_reference: string;
    payment_notes: string;
    payment_date: string;
    receipt_number: string;
    payment_proof: Array<{
        name: string;
        path: string;
        size: number;
        mime_type: string;
        uploaded_at: string;
    }>;
    submitted_at: string;
    processed_at: string;
    completed_at: string;
    staff_notes: string;
    officer_of_the_day?: string;
    rejection_reason: string;
    required_documents: string[];
    submitted_documents: Array<{
        document_type: string;
        name: string;
        path: string;
        size: number;
        mime_type: string;
    }>;
    generated_document_data?: {
        content?: string;
        generated_at?: string;
        generated_by?: number;
    };
    resident_input_data?: Record<string, string>;
    document_type?: {
        id: number;
        name: string;
        code: string;
        input_fields?: Array<{
            key: string;
            label: string;
            type?: string;
            required?: boolean;
            placeholder?: string | null;
            options?: string[];
        }>;
    };
    resident: {
        id: number;
        name: string;
        email: string;
        phone: string;
        address?: string;
        purok?: string;
    };
    staff: {
        id: number;
        name: string;
    };
    payment_verifier: {
        id: number;
        name: string;
    };
}

interface DefaultTemplate {
    id: number;
    name: string;
    template_content: string;
}

interface Props {
    transaction: Transaction;
    defaultTemplate?: DefaultTemplate | null;
}

const props = defineProps<Props>();

const requestFieldTypeLabel = (type?: string): string => {
    const map: Record<string, string> = {
        text: 'Short text',
        textarea: 'Paragraph',
        number: 'Number',
        date: 'Date',
        email: 'Email',
        select: 'Dropdown',
    };
    return map[type ?? 'text'] ?? 'Text';
};

/** All configured request fields for this document type, with resident answers (or empty). */
const staffRequestFieldRows = computed(() => {
    const defs = props.transaction.document_type?.input_fields ?? [];
    const answers = props.transaction.resident_input_data ?? {};
    if (defs.length > 0) {
        return defs.map((d) => {
            const raw = answers[d.key];
            const value =
                raw !== null && raw !== undefined && String(raw).trim() !== '' ? String(raw) : '';
            return {
                key: d.key,
                label: d.label,
                required: d.required !== false,
                typeLabel: requestFieldTypeLabel(d.type),
                value,
            };
        });
    }
    return Object.entries(answers).map(([key, val]) => ({
        key,
        label: key.replace(/_/g, ' '),
        required: false,
        typeLabel: 'Text',
        value: val === null || val === undefined ? '' : String(val),
    }));
});

// Composables
const { staffTransactionShowBreadcrumbs } = useBreadcrumbs();
const { formatPeso, formatDateShort, formatDateTime } = useUtils();

// Breadcrumbs
const breadcrumbs = staffTransactionShowBreadcrumbs.value(props.transaction.transaction_id);

// Form for updating transaction
const form = useForm({
    status: props.transaction.status,
    staff_notes: props.transaction.staff_notes || '',
    officer_of_the_day: props.transaction.officer_of_the_day || '',
    rejection_reason: props.transaction.rejection_reason || '',
    document_content: props.transaction.generated_document_data?.content || '',
});

// File viewer modal state
const viewerOpen = ref(false);
const currentFile = ref<{
    document_type: string;
    name: string;
    path: string;
    size: number;
    mime_type: string;
} | null>(null);

// Certificate generation modal state
const certificateSheetOpen = ref(false);
const certificateForm = useForm({
    status: props.transaction.status,
    officer_of_the_day: props.transaction.officer_of_the_day || '',
    document_content: props.transaction.generated_document_data?.content || '',
});

// Update form when sheet opens to ensure latest data is loaded
const openCertificateSheet = () => {
    // Refresh form data with latest transaction data
    certificateForm.status = props.transaction.status;
    certificateForm.officer_of_the_day = props.transaction.officer_of_the_day || '';
    certificateForm.document_content = props.transaction.generated_document_data?.content || '';
    certificateSheetOpen.value = true;
};
const isGeneratingAI = ref(false);

// Zoom and pan state
const zoomLevel = ref(100);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const imagePosition = ref({ x: 0, y: 0 });
const isFullscreen = ref(false);
const imageRef = ref<HTMLImageElement | null>(null);

// Methods
const loadTemplateContent = async () => {
    try {
        const response = await fetch(`/staff/transactions/${props.transaction.id}/load-template`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            form.document_content = data.content;
            toast.success(`Template "${data.template_name}" loaded successfully`);
        } else {
            const error = await response.json();
            toast.error(error.error || 'Failed to load template');
        }
    } catch (error) {
        console.error('Error loading template:', error);
        toast.error('Failed to load template');
    }
};

const saveDocumentContent = () => {
    form.put(`/staff/transactions/${props.transaction.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Document content saved successfully');
        },
        onError: () => {
            toast.error('Failed to save document content');
        },
    });
};

const submit = () => {
    form.put(`/staff/transactions/${props.transaction.id}`, {
        onSuccess: () => {
            toast.success('Transaction updated successfully!', {
                description: `Status changed to ${form.status.replace('_', ' ')}.`,
                action: {
                    label: 'Refresh',
                    onClick: () => window.location.reload()
                }
            });
            // Refresh the page to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },
        onError: (errors) => {
            toast.error('Failed to update transaction', {
                description: errors.message || 'Please check your input and try again.'
            });
        }
    });
};

const assignToMe = () => {
    // Check if transaction is pending before attempting assignment
    if (props.transaction.status !== 'pending') {
        toast.error('Cannot assign transaction', {
            description: `Transaction status is ${props.transaction.status.replace('_', ' ')}, only pending transactions can be assigned.`
        });
        return;
    }

    const assignForm = useForm({});
    assignForm.post(`/staff/transactions/${props.transaction.id}/assign`, {
        onSuccess: () => {
            toast.success('Transaction assigned successfully!', {
                description: 'You have been assigned to this transaction and it is now in progress.',
                action: {
                    label: 'Refresh',
                    onClick: () => window.location.reload()
                }
            });
            // Refresh the page to show updated assignment and status
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const downloadFile = (file: { name: string; path: string }) => {
    // Create a download link
    const link = document.createElement('a');
    link.href = `/storage/${file.path}`;
    link.download = file.name;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};


const isImageFile = (mimeType: string): boolean => {
    return mimeType.startsWith('image/');
};

const isPdfFile = (mimeType: string): boolean => {
    return mimeType === 'application/pdf';
};

const getFileIconComponent = (mimeType: string): LucideIcon => {
    if (isImageFile(mimeType)) {
        return Image;
    }
    if (isPdfFile(mimeType) || mimeType.includes('word')) {
        return FileText;
    }
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return FileSpreadsheet;
    }
    return Folder;
};

// Zoom and pan functions
const zoomIn = () => {
    zoomLevel.value = Math.min(zoomLevel.value + 25, 500);
};

const zoomOut = () => {
    zoomLevel.value = Math.max(zoomLevel.value - 25, 25);
};

const resetZoom = () => {
    zoomLevel.value = 100;
    imagePosition.value = { x: 0, y: 0 };
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
    if (!isFullscreen.value) {
        resetZoom();
    }
};

const handleWheel = (event: WheelEvent) => {
    event.preventDefault();
    if (event.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
};

const handleMouseDown = (event: MouseEvent) => {
    if (zoomLevel.value > 100) {
        isDragging.value = true;
        dragStart.value = { x: event.clientX - imagePosition.value.x, y: event.clientY - imagePosition.value.y };
        document.body.style.cursor = 'grabbing';
    }
};

const handleMouseMove = (event: MouseEvent) => {
    if (isDragging.value && zoomLevel.value > 100) {
        imagePosition.value = {
            x: event.clientX - dragStart.value.x,
            y: event.clientY - dragStart.value.y
        };
    }
};

const handleMouseUp = () => {
    isDragging.value = false;
    document.body.style.cursor = 'default';
};

const handleKeydown = (event: KeyboardEvent) => {
    if (!viewerOpen.value) return;

    switch (event.key) {
        case '+':
        case '=':
            event.preventDefault();
            zoomIn();
            break;
        case '-':
            event.preventDefault();
            zoomOut();
            break;
        case '0':
            event.preventDefault();
            resetZoom();
            break;
        case 'Escape':
            event.preventDefault();
            closeFileViewer();
            break;
        case 'f':
        case 'F':
            event.preventDefault();
            toggleFullscreen();
            break;
    }
};

const openFileViewer = (file: {
    document_type: string;
    name: string;
    path: string;
    size: number;
    mime_type: string;
}) => {
    currentFile.value = file;
    viewerOpen.value = true;
    resetZoom(); // Reset zoom when opening new file
};

const closeFileViewer = () => {
    viewerOpen.value = false;
    currentFile.value = null;
    isFullscreen.value = false;
    resetZoom();
    document.body.style.cursor = 'default';
};

// Payment helper functions
const getPaymentStatusColor = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getPaymentMethodDisplay = (method: string) => {
    switch (method) {
        case 'cash': return 'Cash';
        case 'gcash': return 'GCash';
        case 'paymaya': return 'PayMaya';
        case 'bank_transfer': return 'Bank Transfer';
        case 'check': return 'Check';
        default: return method;
    }
};

const canProcessPayment = () => {
    return props.transaction.payment_status === 'pending' || props.transaction.payment_status === 'failed';
};


const generateWithAI = async () => {
    isGeneratingAI.value = true;
    try {
        const response = await fetch(`/staff/transactions/${props.transaction.id}/generate-ai`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.content) {
                certificateForm.document_content = data.content;
                toast.success('Certificate content generated successfully using AI!');
            } else {
                toast.error('No content generated');
            }
        } else {
            const errorData = await response.json();
            toast.error(errorData.error || 'Failed to generate content with AI');
        }
    } catch (error) {
        console.error('Error generating with AI:', error);
        toast.error('Error generating content with AI');
    } finally {
        isGeneratingAI.value = false;
    }
};

const saveCertificate = () => {
    // Ensure officer_of_the_day is always a string (never null/undefined)
    const officerValue = String(certificateForm.officer_of_the_day || '').trim();
    
    // Explicitly set all form fields to ensure they're included
    certificateForm.status = certificateForm.status || props.transaction.status;
    certificateForm.officer_of_the_day = officerValue; // Always a string, even if empty
    certificateForm.document_content = certificateForm.document_content || '';
    
    // Prepare the data object explicitly to ensure officer_of_the_day is always included
    const submitData = {
        status: certificateForm.status,
        officer_of_the_day: officerValue || null, // Use null instead of empty string to ensure it's sent
        document_content: certificateForm.document_content || '',
    };
    
    // Use transform to ensure all fields are sent, even if empty
    certificateForm.transform(() => {
        // Always return the explicitly prepared data
        return submitData;
    }).put(`/staff/transactions/${props.transaction.id}`, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Update the form with the latest data from the response
            if (page.props.transaction) {
                certificateForm.officer_of_the_day = page.props.transaction.officer_of_the_day || '';
                certificateForm.document_content = page.props.transaction.generated_document_data?.content || '';
            }
            toast.success('Certificate content saved successfully!');
            certificateSheetOpen.value = false;
        },
        onError: (errors) => {
            console.error('Save errors:', errors);
            toast.error('Failed to save certificate content');
        }
    });
};

const printCertificate = () => {
    window.open(`/staff/transactions/${props.transaction.id}/print-certificate`, '_blank');
};

// Keyboard event listener setup
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head :title="`Transaction ${props.transaction.transaction_id}`" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-full">
                <!-- Header Section -->
                <div class="mb-3 md:mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Link href="/staff/transactions">
                            <Button variant="outline" size="sm" class="flex items-center gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Transactions
                            </Button>
                        </Link>
                    </div>
                    <div class="space-y-1">
                        <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                            Transaction {{ props.transaction.transaction_id }}
                        </h1>
                        <p class="text-base md:text-lg text-gray-600 font-medium">{{ props.transaction.title }}</p>
                    </div>
                </div>

                <!-- Quick Actions Bar -->
                <div class="mb-4">
                    <Card class="shadow-lg border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <CardContent class="p-4">
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <Badge :class="getStatusColor(props.transaction.status)" class="px-3 py-1 text-sm font-bold">
                                        {{ props.transaction.status.replace('_', ' ').toUpperCase() }}
                                    </Badge>
                                    <span class="text-sm text-gray-600">Transaction {{ props.transaction.transaction_id }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        v-if="props.transaction.status === 'pending' && !props.transaction.staff"
                                        @click="assignToMe"
                                        size="sm"
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium"
                                    >
                                        <User class="h-4 w-4 mr-2" />
                                        Assign to Me
                                    </Button>
                                    <Button
                                        v-if="props.transaction.status === 'pending' && props.transaction.staff"
                                        @click="assignToMe"
                                        size="sm"
                                        variant="outline"
                                        class="border-green-600 text-green-600 hover:bg-green-50"
                                    >
                                        <User class="h-4 w-4 mr-2" />
                                        Reassign to Me
                                    </Button>
                                    <div class="flex gap-2">
                                        <Button
                                            v-if="props.transaction.status === 'in_progress' || props.transaction.status === 'completed'"
                                            @click="openCertificateSheet"
                                            size="sm"
                                            class="bg-teal-600 hover:bg-teal-700 text-white font-medium"
                                        >
                                            <FileCheck class="h-4 w-4 mr-2" />
                                            Generate Certificate
                                        </Button>
                                        <Link
                                            v-if="canProcessPayment()"
                                            :href="`/staff/transactions/${props.transaction.id}/payment`"
                                            class="inline-flex"
                                        >
                                            <Button
                                                size="sm"
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium"
                                            >
                                                <DollarSign class="h-4 w-4 mr-2" />
                                                Process Payment
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="(props.transaction.status === 'in_progress' || props.transaction.status === 'completed') && props.transaction.generated_document_data?.content"
                                            @click="printCertificate"
                                            size="sm"
                                            class="bg-green-600 hover:bg-green-700 text-white font-medium"
                                        >
                                            <Printer class="h-4 w-4 mr-2" />
                                            Print Certificate
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Transaction Overview -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <FileText class="h-4 w-4" />
                                    Transaction Overview
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Document Type</Label>
                                        <p class="text-lg font-semibold text-gray-900">{{ props.transaction.title }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Fee Amount</Label>
                                        <p class="text-lg font-semibold text-green-600">{{ formatPeso(props.transaction.fee_amount) }}</p>
                                    </div>
                                    <div v-if="props.transaction.payment_status">
                                        <Label class="text-sm font-medium text-gray-700">Payment Status</Label>
                                        <div class="flex items-center gap-2">
                                            <Badge :class="getPaymentStatusColor(props.transaction.payment_status)" class="capitalize">
                                                {{ props.transaction.payment_status }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div v-if="props.transaction.payment_method">
                                        <Label class="text-sm font-medium text-gray-700">Payment Method</Label>
                                        <p class="text-sm font-medium">{{ getPaymentMethodDisplay(props.transaction.payment_method) }}</p>
                                    </div>
                                    <div v-if="props.transaction.amount_paid">
                                        <Label class="text-sm font-medium text-gray-700">Amount Paid</Label>
                                        <p class="text-lg font-semibold text-green-600">{{ formatPeso(props.transaction.amount_paid) }}</p>
                                    </div>
                                    <div v-if="props.transaction.receipt_number">
                                        <Label class="text-sm font-medium text-gray-700">Receipt Number</Label>
                                        <p class="text-sm font-medium font-mono">{{ props.transaction.receipt_number }}</p>
                                    </div>
                                </div>
                                <div v-if="props.transaction.description">
                                    <Label class="text-sm font-medium text-gray-700">Description</Label>
                                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">{{ props.transaction.description }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Documents Status -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-lg">Documents Status</CardTitle>
                                <CardDescription class="text-sm">
                                    Required documents, request fields, and submitted files
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Required Documents -->
                                <div>
                                    <Label class="text-sm font-medium text-gray-700 mb-2 block">Required Documents</Label>
                                    <div v-if="props.transaction.required_documents && props.transaction.required_documents.length > 0" class="space-y-2">
                                        <div v-for="(doc, index) in props.transaction.required_documents" :key="index" class="flex items-center gap-2 p-2 bg-blue-50 rounded-lg">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            <span class="text-sm text-gray-700">{{ doc }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 italic p-2 bg-gray-50 rounded-lg">
                                        No specific documents required
                                    </div>
                                </div>

                                <!-- Required request fields (document type schema + answers) -->
                                <div v-if="staffRequestFieldRows.length > 0" class="pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <ClipboardList class="h-4 w-4 text-cyan-600" />
                                        <Label class="text-sm font-medium text-gray-700">Required request fields</Label>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Extra questions defined for this document type and what the resident entered.
                                    </p>
                                    <div class="space-y-2">
                                        <div
                                            v-for="row in staffRequestFieldRows"
                                            :key="row.key"
                                            class="flex flex-col gap-2 p-3 rounded-lg border border-cyan-100 bg-gradient-to-br from-cyan-50/80 to-sky-50/40"
                                        >
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-gray-900">{{ row.label }}</span>
                                                <Badge variant="outline" class="text-[10px] font-normal">{{ row.typeLabel }}</Badge>
                                                <Badge
                                                    v-if="row.required"
                                                    variant="secondary"
                                                    class="text-[10px] bg-amber-100 text-amber-900 border-amber-200"
                                                >
                                                    Required
                                                </Badge>
                                                <Badge v-else variant="outline" class="text-[10px] text-gray-500 font-normal">
                                                    Optional
                                                </Badge>
                                            </div>
                                            <div>
                                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Answer</span>
                                                <p class="text-sm text-gray-900 mt-1 whitespace-pre-wrap break-words font-medium">
                                                    {{
                                                        row.value ||
                                                            (row.required ? 'Not provided yet' : '—')
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submitted Documents -->
                                <div>
                                    <Label class="text-sm font-medium text-gray-700 mb-2 block">Submitted Documents</Label>
                                    <div v-if="props.transaction.submitted_documents && props.transaction.submitted_documents.length > 0" class="space-y-3">
                                        <div v-for="(file, index) in props.transaction.submitted_documents" :key="index" class="border border-gray-200 rounded-lg p-3 bg-white hover:bg-gray-50 transition-colors">
                                            <!-- Mobile-first responsive layout -->
                                            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                                <!-- File Info Section -->
                                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                                    <div class="flex-shrink-0 mt-1">
                                                        <File class="h-5 w-5 text-blue-600" />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <!-- File name and badge - stack on mobile -->
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                                                            <span class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</span>
                                                            <Badge variant="outline" class="text-xs self-start sm:self-center">{{ file.document_type.replace('_', ' ').toUpperCase() }}</Badge>
                                                        </div>
                                                        <!-- File details - responsive layout -->
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs text-gray-500">
                                                            <span>{{ formatFileSize(file.size) }}</span>
                                                            <span class="hidden sm:inline">{{ file.mime_type }}</span>
                                                            <span class="sm:hidden">{{ file.mime_type.split('/')[1]?.toUpperCase() || file.mime_type }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons - responsive layout -->
                                                <div class="flex gap-2 sm:flex-shrink-0">
                                                    <Button
                                                        @click="openFileViewer(file)"
                                                        size="sm"
                                                        variant="outline"
                                                        class="flex-1 sm:flex-none h-8 px-3 text-green-600 border-green-200 hover:bg-green-50"
                                                    >
                                                        <Eye class="h-3 w-3 sm:mr-1" />
                                                        <span class="sm:inline">View</span>
                                                    </Button>
                                                    <Button
                                                        @click="downloadFile(file)"
                                                        size="sm"
                                                        variant="outline"
                                                        class="flex-1 sm:flex-none h-8 px-3 text-blue-600 border-blue-200 hover:bg-blue-50"
                                                    >
                                                        <Download class="h-3 w-3 sm:mr-1" />
                                                        <span class="sm:inline">Download</span>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 italic p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        No documents submitted yet
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Resident Information -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <User class="h-4 w-4" />
                                    Resident Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Name</Label>
                                        <p class="text-sm text-gray-900 font-medium">{{ props.transaction.resident.name }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Email</Label>
                                        <p class="text-sm text-gray-900">{{ props.transaction.resident.email }}</p>
                                    </div>
                                    <div v-if="props.transaction.resident.phone">
                                        <Label class="text-sm font-medium text-gray-700">Phone</Label>
                                        <p class="text-sm text-gray-900">{{ props.transaction.resident.phone }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Action Panel -->
                    <div class="space-y-4">
                        <!-- Update Transaction -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-lg">Update Transaction</CardTitle>
                                <CardDescription class="text-sm">Change status and add notes</CardDescription>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <form @submit.prevent="submit" class="space-y-4">
                                    <div>
                                        <Label for="status" class="text-sm font-medium text-gray-700">Status</Label>
                                        <Select v-model="form.status">
                                            <SelectTrigger class="h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="in_progress">In Progress</SelectItem>
                                                <SelectItem value="completed">Completed</SelectItem>
                                                <SelectItem value="rejected">Rejected</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label for="staff_notes" class="text-sm font-medium text-gray-700">Staff Notes</Label>
                                        <Textarea
                                            id="staff_notes"
                                            v-model="form.staff_notes"
                                            placeholder="Add internal notes..."
                                            rows="3"
                                            class="border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>

                                    <div v-if="form.status === 'rejected'">
                                        <Label for="rejection_reason" class="text-sm font-medium text-gray-700">Rejection Reason</Label>
                                        <Textarea
                                            id="rejection_reason"
                                            v-model="form.rejection_reason"
                                            placeholder="Reason for rejection..."
                                            rows="2"
                                            class="border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium h-10"
                                    >
                                        {{ form.processing ? 'Updating...' : 'Update Transaction' }}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <!-- Timeline -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <Calendar class="h-4 w-4" />
                                    Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3 pt-0">
                                <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Submitted</Label>
                                        <p class="text-sm text-gray-900">{{ formatDateTime(props.transaction.submitted_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="props.transaction.processed_at" class="flex items-center gap-3 p-2 bg-blue-50 rounded-lg">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Processed</Label>
                                        <p class="text-sm text-gray-900">{{ formatDateTime(props.transaction.processed_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="props.transaction.completed_at" class="flex items-center gap-3 p-2 bg-green-50 rounded-lg">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div>
                                        <Label class="text-xs font-medium text-gray-500">Completed</Label>
                                        <p class="text-sm text-gray-900">{{ formatDateTime(props.transaction.completed_at) }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Staff Assignment -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-lg">Assignment</CardTitle>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div v-if="props.transaction.staff" class="p-3 bg-green-50 rounded-lg">
                                    <Label class="text-xs font-medium text-gray-500">Assigned to</Label>
                                    <p class="text-sm text-gray-900 font-medium">{{ props.transaction.staff.name }}</p>
                                </div>
                                <div v-else class="p-3 bg-yellow-50 rounded-lg">
                                    <Label class="text-xs font-medium text-gray-500">Status</Label>
                                    <p class="text-sm text-gray-900">Unassigned</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Viewer Modal -->
        <Dialog :open="viewerOpen" @update:open="closeFileViewer">
            <TooltipProvider :delay-duration="200">
                <DialogContent
                    :class="[
                        '!flex !max-h-[min(90vh,100dvh)] !w-[min(calc(100vw-1.5rem),56rem)] !flex-col !gap-0 !p-0 !sm:max-w-4xl overflow-hidden shadow-xl',
                        isFullscreen ? '!fixed !inset-2 !h-[calc(100dvh-1rem)] !w-[calc(100vw-1rem)] !max-h-none !max-w-none !translate-x-0 !translate-y-0 sm:!inset-4' : '',
                    ]"
                >
                    <DialogHeader class="shrink-0 space-y-0 border-b border-border bg-background px-4 py-3 pr-12 text-left sm:px-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <DialogTitle class="flex min-w-0 items-center gap-2 text-base font-semibold leading-snug sm:text-lg">
                                <component
                                    :is="currentFile ? getFileIconComponent(currentFile.mime_type) : Folder"
                                    class="h-6 w-6 shrink-0 text-muted-foreground sm:h-7 sm:w-7"
                                />
                                <span class="truncate" :title="currentFile?.name">{{ currentFile?.name }}</span>
                            </DialogTitle>

                            <div
                                v-if="currentFile && isImageFile(currentFile.mime_type)"
                                class="flex flex-wrap items-center gap-1.5 sm:shrink-0"
                            >
                                <Button type="button" @click="zoomOut" size="sm" variant="outline" :disabled="zoomLevel <= 25" aria-label="Zoom out">
                                    <ZoomOut class="h-4 w-4" />
                                </Button>
                                <span class="min-w-[2.75rem] text-center text-sm font-medium tabular-nums">{{ zoomLevel }}%</span>
                                <Button type="button" @click="zoomIn" size="sm" variant="outline" :disabled="zoomLevel >= 500" aria-label="Zoom in">
                                    <ZoomIn class="h-4 w-4" />
                                </Button>
                                <Button type="button" @click="resetZoom" size="sm" variant="outline" aria-label="Reset zoom">
                                    <RotateCcw class="h-4 w-4" />
                                </Button>
                                <Button type="button" @click="toggleFullscreen" size="sm" variant="outline" aria-label="Toggle fullscreen">
                                    <Maximize2 v-if="!isFullscreen" class="h-4 w-4" />
                                    <Minimize2 v-else class="h-4 w-4" />
                                </Button>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button type="button" size="sm" variant="ghost" class="h-8 w-8 p-0" aria-label="Keyboard shortcuts">
                                            <HelpCircle class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent side="bottom" align="end" class="max-w-xs text-left">
                                        <p class="text-xs leading-relaxed">
                                            <span class="font-medium">Shortcuts:</span>
                                            + / − zoom · 0 reset · F fullscreen · Esc close · wheel zoom when over image
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </div>
                    </DialogHeader>

                    <div v-if="currentFile" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-muted/20 px-3 py-3 sm:px-4">
                        <!-- Image Viewer with Zoom -->
                        <div
                            v-if="isImageFile(currentFile.mime_type)"
                            class="relative min-h-[min(50vh,24rem)] flex-1 overflow-hidden rounded-lg border border-border/80 bg-muted"
                            @wheel="handleWheel"
                            @mousedown="handleMouseDown"
                            @mousemove="handleMouseMove"
                            @mouseup="handleMouseUp"
                            @mouseleave="handleMouseUp"
                        >
                            <img
                                ref="imageRef"
                                :src="`/storage/${currentFile.path}`"
                                :alt="currentFile.name"
                                :style="{
                                    transform: `scale(${zoomLevel / 100}) translate(${imagePosition.x}px, ${imagePosition.y}px)`,
                                    transformOrigin: 'center center',
                                    transition: isDragging ? 'none' : 'transform 0.2s ease-out',
                                    cursor: zoomLevel > 100 ? (isDragging ? 'grabbing' : 'grab') : 'default',
                                }"
                                class="absolute top-1/2 left-1/2 max-h-none max-w-none -translate-x-1/2 -translate-y-1/2 object-contain"
                            />

                            <div
                                v-if="zoomLevel > 100"
                                class="pointer-events-none absolute left-3 top-3 rounded-md bg-black/75 px-2.5 py-1.5 text-xs text-white shadow-md sm:text-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <Move class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" />
                                    <span>Drag to pan</span>
                                </div>
                            </div>
                        </div>

                        <!-- PDF Viewer -->
                        <div v-else-if="isPdfFile(currentFile.mime_type)" class="min-h-[min(60vh,32rem)] flex-1 overflow-hidden rounded-lg border border-border/80 bg-background">
                            <iframe
                                :src="`/storage/${currentFile.path}`"
                                class="h-full min-h-[min(50vh,24rem)] w-full border-0"
                                title="PDF Viewer"
                            ></iframe>
                        </div>

                        <!-- Unsupported File Type -->
                        <div v-else class="flex min-h-[min(50vh,20rem)] flex-1 flex-col items-center justify-center rounded-lg border border-dashed border-border bg-background px-4 text-center">
                            <component
                                :is="getFileIconComponent(currentFile.mime_type)"
                                class="mb-4 h-16 w-16 shrink-0 text-muted-foreground"
                            />
                            <h3 class="mb-2 text-lg font-semibold text-foreground sm:text-xl">{{ currentFile.name }}</h3>
                            <p class="mb-4 flex flex-wrap items-center justify-center gap-x-1.5 gap-y-0.5 text-sm text-muted-foreground">
                                <span>{{ formatFileSize(currentFile.size) }}</span>
                                <span class="text-border" aria-hidden="true">·</span>
                                <span>{{ currentFile.mime_type }}</span>
                            </p>
                            <p class="mb-6 max-w-sm text-sm text-muted-foreground">This file type cannot be previewed in the browser.</p>
                            <Button type="button" @click="downloadFile(currentFile)" class="bg-blue-600 hover:bg-blue-700">
                                <Download class="mr-2 h-4 w-4" />
                                Download to View
                            </Button>
                        </div>
                    </div>

                    <!-- File info + download: stable footer, no overlap -->
                    <div
                        v-if="currentFile"
                        class="shrink-0 border-t border-border bg-background px-4 py-4 sm:px-5"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-stretch sm:justify-between sm:gap-6">
                            <dl class="grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-6">
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Type</dt>
                                    <dd class="mt-1 truncate text-sm font-medium text-foreground" :title="currentFile.document_type.replace('_', ' ').toUpperCase()">
                                        {{ currentFile.document_type.replace('_', ' ').toUpperCase() }}
                                    </dd>
                                </div>
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Size</dt>
                                    <dd class="mt-1 text-sm font-medium text-foreground">
                                        {{ formatFileSize(currentFile.size) }}
                                    </dd>
                                </div>
                                <div class="min-w-0 sm:col-span-1">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Format</dt>
                                    <dd class="mt-1 truncate font-mono text-sm text-foreground" :title="currentFile.mime_type">
                                        {{ currentFile.mime_type }}
                                    </dd>
                                </div>
                            </dl>
                            <div class="flex shrink-0 sm:items-end">
                                <Button
                                    type="button"
                                    @click="downloadFile(currentFile)"
                                    variant="outline"
                                    size="default"
                                    class="h-10 w-full min-w-[9rem] sm:w-auto sm:self-center"
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    Download
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </TooltipProvider>
        </Dialog>

        <!-- Certificate Generation Sheet -->
        <Sheet v-model:open="certificateSheetOpen">
            <SheetContent class="p-0 flex flex-col h-full w-full sm:max-w-5xl overflow-hidden">
                <!-- Professional Header -->
                <SheetHeader class="p-6 pb-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 via-emerald-50 to-teal-50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-teal-100 rounded-lg">
                                <FileCheck class="h-6 w-6 text-teal-700" />
                            </div>
                            <div>
                                <SheetTitle class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    Generate Certificate/Permit
                                </SheetTitle>
                                <SheetDescription class="text-gray-600 mt-1.5 text-sm">
                                    Create and customize the official document for <span class="font-semibold text-gray-900">{{ props.transaction.document_type?.name || 'this transaction' }}</span>
                                </SheetDescription>
                            </div>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Main Content Area -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-6 space-y-6">
                        <!-- Quick Actions Bar -->
                        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-blue-100 rounded-lg">
                                    <Sparkles class="h-4 w-4 text-blue-700" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">AI Generation</p>
                                    <p class="text-xs text-gray-600">Generate certificate content using AI with all transaction data</p>
                                </div>
                            </div>
                            <Button
                                @click="generateWithAI"
                                variant="outline"
                                size="default"
                                class="border-blue-300 text-blue-700 hover:bg-blue-100 hover:text-blue-800 font-medium shadow-sm"
                                :disabled="isGeneratingAI"
                            >
                                <Sparkles class="h-4 w-4 mr-2" />
                                {{ isGeneratingAI ? 'Generating...' : 'Generate using AI' }}
                            </Button>
                        </div>

                        <!-- Two Column Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Main Editor Column (2/3 width) -->
                            <div class="lg:col-span-2 space-y-4">
                                <!-- Document Content Section -->
                                <Card class="shadow-sm border-gray-200">
                                    <CardHeader class="pb-3 border-b border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <CardTitle class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                                    <FileText class="h-5 w-5 text-teal-600" />
                                                    Document Content
                                                </CardTitle>
                                                <CardDescription class="text-sm text-gray-600 mt-1">
                                                    Compose and format your certificate or permit content
                                                </CardDescription>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="pt-6">
                                        <div class="space-y-3">
                                            <div class="border-2 border-gray-200 rounded-lg overflow-hidden bg-white shadow-inner">
                                                <RichTextEditor
                                                    v-model="certificateForm.document_content"
                                                    class="min-h-[550px]"
                                                />
                                            </div>
                                            <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                                <div class="p-1 bg-amber-100 rounded">
                                                    <AlertCircle class="h-4 w-4 text-amber-700 flex-shrink-0 mt-0.5" />
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-xs font-medium text-amber-900">Pro Tip</p>
                                                    <p class="text-xs text-amber-800 mt-0.5">
                                                        Use the formatting toolbar above to style your document. All changes are saved automatically when you click "Save Certificate".
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Sidebar Column (1/3 width) -->
                            <div class="lg:col-span-1 space-y-4">
                                <!-- Resident Information Card -->
                                <Card class="shadow-sm border-gray-200 bg-gradient-to-br from-gray-50 to-white">
                                    <CardHeader class="pb-3 border-b border-gray-100">
                                        <CardTitle class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                            <User class="h-4 w-4 text-blue-600" />
                                            Resident Details
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="pt-4 space-y-4">
                                        <div class="space-y-3">
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Full Name</Label>
                                                <p class="text-sm font-semibold text-gray-900 mt-1">{{ props.transaction.resident?.name || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Address</Label>
                                                <p class="text-sm text-gray-700 mt-1 leading-relaxed">{{ props.transaction.resident?.address || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Purok</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ props.transaction.resident?.purok || 'N/A' }}</p>
                                            </div>
                                            <div v-if="props.transaction.resident?.email">
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ props.transaction.resident.email }}</p>
                                            </div>
                                            <div v-if="props.transaction.resident?.phone">
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</Label>
                                                <p class="text-sm text-gray-700 mt-1">{{ props.transaction.resident.phone }}</p>
                                            </div>
                                        </div>

                                        <!-- Required request fields (same data as Documents card, compact) -->
                                        <div v-if="staffRequestFieldRows.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                            <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1 block">
                                                Required request fields
                                            </Label>
                                            <p class="text-[11px] text-gray-400 mb-2">Configured questions and answers</p>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="row in staffRequestFieldRows"
                                                    :key="row.key"
                                                    class="bg-white rounded-lg p-2.5 border border-gray-200"
                                                >
                                                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                        <span class="text-xs font-semibold text-gray-800">{{ row.label }}</span>
                                                        <Badge v-if="row.required" variant="outline" class="text-[9px] px-1 py-0 h-4">
                                                            Req.
                                                        </Badge>
                                                    </div>
                                                    <p class="text-sm text-gray-900 font-medium leading-snug">
                                                        {{
                                                            row.value ||
                                                                (row.required ? 'Not provided yet' : '—')
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Document Type Info -->
                                <Card class="shadow-sm border-gray-200 bg-gradient-to-br from-teal-50 to-emerald-50">
                                    <CardHeader class="pb-3 border-b border-teal-100">
                                        <CardTitle class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-teal-700" />
                                            Document Type
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="pt-4">
                                        <div class="space-y-2">
                                            <div>
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Type</Label>
                                                <p class="text-sm font-semibold text-gray-900 mt-1">{{ props.transaction.document_type?.name || 'N/A' }}</p>
                                            </div>
                                            <div v-if="props.transaction.document_type?.code">
                                                <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Code</Label>
                                                <Badge variant="outline" class="mt-1 text-xs">{{ props.transaction.document_type.code }}</Badge>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Officer of the Day -->
                                <Card class="shadow-lg border-2 border-amber-300 bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-50">
                                    <CardHeader class="pb-3 border-b-2 border-amber-200 bg-gradient-to-r from-amber-100 to-yellow-100">
                                        <CardTitle class="text-base font-bold text-amber-900 flex items-center gap-2">
                                            <div class="p-1.5 bg-amber-200 rounded-lg">
                                                <User class="h-4 w-4 text-amber-800" />
                                            </div>
                                            Signatory Information
                                            <Badge variant="outline" class="ml-auto bg-amber-200 text-amber-900 border-amber-300 font-semibold text-xs">
                                                SPECIAL FIELD
                                            </Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="pt-4">
                                        <div class="space-y-3">
                                            <!-- Important Notice -->
                                            <div class="bg-amber-100 border-l-4 border-amber-500 p-3 rounded-r-lg">
                                                <div class="flex items-start gap-2">
                                                    <AlertCircle class="h-4 w-4 text-amber-700 flex-shrink-0 mt-0.5" />
                                                    <div class="flex-1">
                                                        <p class="text-xs font-bold text-amber-900 uppercase tracking-wide">Non-Standard Field</p>
                                                        <p class="text-xs text-amber-800 mt-1">
                                                            This field is only used when the Punong Barangay is absent. Leave empty for standard certificates.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <Label for="certificate_officer_of_the_day" class="text-sm font-bold text-amber-900 mb-2 block flex items-center gap-2">
                                                    <span>Officer of the Day</span>
                                                    <Badge variant="outline" class="bg-amber-200 text-amber-900 border-amber-300 text-xs px-1.5 py-0">
                                                        Optional
                                                    </Badge>
                                                </Label>
                                                <input
                                                    id="certificate_officer_of_the_day"
                                                    type="text"
                                                    v-model="certificateForm.officer_of_the_day"
                                                    placeholder="e.g., CHARLITA G. MONTENEGRO, RSW"
                                                    class="w-full border-2 border-amber-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-400 text-sm font-medium bg-white shadow-sm h-9 rounded-md px-3 py-1 outline-none transition-[color,box-shadow]"
                                                />
                                                <p class="flex items-start gap-2 text-xs text-amber-700 mt-2 font-medium">
                                                    <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5 text-amber-600" />
                                                    <span>
                                                        <strong>Important:</strong> This name will appear on the printed certificate as "Officer of the Day" if provided. Leave empty if Punong Barangay will sign.
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="pt-3 border-t-2 border-amber-200">
                                                <Label class="text-xs font-bold text-amber-900 uppercase tracking-wide mb-2 block">Standard Signatory</Label>
                                                <div class="bg-white rounded-lg p-2.5 border border-amber-200">
                                                    <p class="text-sm font-bold text-gray-900">EMMANUEL P. ISIANG</p>
                                                    <p class="text-xs text-gray-700 mt-0.5">Punong Barangay</p>
                                                    <p class="text-xs text-gray-600 mt-0.5">President – <span class="text-red-600 underline">Liga ng mga Barangay</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Footer Actions -->
                <div class="border-t border-gray-200 bg-gray-50/50 p-6">
                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="p-1.5 bg-gray-200 rounded">
                                <FileCheck class="h-3.5 w-3.5 text-gray-600" />
                            </div>
                            <span>All changes will be saved to the transaction record</span>
                        </div>
                        <div class="flex gap-3">
                            <Button
                                @click="certificateSheetOpen = false"
                                variant="outline"
                                size="default"
                                class="border-gray-300 text-gray-700 hover:bg-gray-100 font-medium min-w-[100px]"
                            >
                                Cancel
                            </Button>
                            <Button
                                @click="saveCertificate"
                                size="default"
                                class="bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-semibold shadow-md hover:shadow-lg transition-all min-w-[160px]"
                                :disabled="certificateForm.processing"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                {{ certificateForm.processing ? 'Saving...' : 'Save Certificate' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </StaffLayout>
</template>

