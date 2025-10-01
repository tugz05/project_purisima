<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ArrowLeft, Clock, CheckCircle, XCircle, AlertCircle, User, FileText, Calendar, DollarSign, Download, File, Eye, X, ZoomIn, ZoomOut, RotateCcw, Maximize2, Minimize2 } from 'lucide-vue-next';
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
    rejection_reason: string;
    required_documents: string[];
    submitted_documents: Array<{
        document_type: string;
        name: string;
        path: string;
        size: number;
        mime_type: string;
    }>;
    resident: {
        id: number;
        name: string;
        email: string;
        phone: string;
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

interface Props {
    transaction: Transaction;
}

const props = defineProps<Props>();

// Composables
const { staffTransactionShowBreadcrumbs } = useBreadcrumbs();
const { formatPeso, formatDateShort, formatDateTime } = useUtils();

// Breadcrumbs
const breadcrumbs = staffTransactionShowBreadcrumbs.value(props.transaction.transaction_id);

// Form for updating transaction
const form = useForm({
    status: props.transaction.status,
    staff_notes: props.transaction.staff_notes || '',
    rejection_reason: props.transaction.rejection_reason || '',
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

// Zoom and pan state
const zoomLevel = ref(100);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const imagePosition = ref({ x: 0, y: 0 });
const isFullscreen = ref(false);
const imageRef = ref<HTMLImageElement | null>(null);

// Methods
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

const getFileIcon = (mimeType: string) => {
    if (isImageFile(mimeType)) return '🖼️';
    if (isPdfFile(mimeType)) return '📄';
    if (mimeType.includes('word')) return '📝';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊';
    return '📁';
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
                                <CardDescription class="text-sm">Required vs Submitted documents</CardDescription>
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
            <DialogContent
                :class="isFullscreen ? 'max-w-full max-h-full w-screen h-screen' : 'max-w-4xl max-h-[90vh]'"
                class="overflow-hidden"
            >
                <DialogHeader>
                    <DialogTitle class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ currentFile ? getFileIcon(currentFile.mime_type) : '📁' }}</span>
                            <span>{{ currentFile?.name }}</span>
                        </div>

                        <!-- Zoom Controls -->
                        <div v-if="currentFile && isImageFile(currentFile.mime_type)" class="flex items-center gap-2">
                            <Button @click="zoomOut" size="sm" variant="outline" :disabled="zoomLevel <= 25">
                                <ZoomOut class="h-4 w-4" />
                            </Button>
                            <span class="text-sm font-medium min-w-[3rem] text-center">{{ zoomLevel }}%</span>
                            <Button @click="zoomIn" size="sm" variant="outline" :disabled="zoomLevel >= 500">
                                <ZoomIn class="h-4 w-4" />
                            </Button>
                            <Button @click="resetZoom" size="sm" variant="outline">
                                <RotateCcw class="h-4 w-4" />
                            </Button>
                            <Button @click="toggleFullscreen" size="sm" variant="outline">
                                <Maximize2 v-if="!isFullscreen" class="h-4 w-4" />
                                <Minimize2 v-else class="h-4 w-4" />
                            </Button>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <div v-if="currentFile" class="flex-1 overflow-hidden relative">
                    <!-- Image Viewer with Zoom -->
                    <div
                        v-if="isImageFile(currentFile.mime_type)"
                        class="relative w-full h-[70vh] overflow-hidden bg-gray-100 rounded-lg"
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
                                cursor: zoomLevel > 100 ? (isDragging ? 'grabbing' : 'grab') : 'default'
                            }"
                            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-w-none max-h-none object-contain"
                        />

                        <!-- Zoom Instructions -->
                        <div v-if="zoomLevel > 100" class="absolute top-4 left-4 bg-black/70 text-white px-3 py-2 rounded-lg text-sm">
                            <div class="flex items-center gap-2">
                                <span>🖱️</span>
                                <span>Drag to pan</span>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Viewer -->
                    <div v-else-if="isPdfFile(currentFile.mime_type)" class="w-full h-[70vh]">
                        <iframe
                            :src="`/storage/${currentFile.path}`"
                            class="w-full h-full border-0 rounded-lg"
                            title="PDF Viewer"
                        ></iframe>
                    </div>

                    <!-- Unsupported File Type -->
                    <div v-else class="flex flex-col items-center justify-center h-[70vh] text-center">
                        <div class="text-6xl mb-4">{{ getFileIcon(currentFile.mime_type) }}</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ currentFile.name }}</h3>
                        <p class="text-gray-600 mb-4">{{ formatFileSize(currentFile.size) }} • {{ currentFile.mime_type }}</p>
                        <p class="text-gray-500 mb-6">This file type cannot be previewed in the browser.</p>
                        <Button @click="downloadFile(currentFile)" class="bg-blue-600 hover:bg-blue-700">
                            <Download class="h-4 w-4 mr-2" />
                            Download to View
                        </Button>
                    </div>
                </div>

                <!-- File Info Footer -->
                <div v-if="currentFile" class="border-t pt-4 mt-4">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div class="flex items-center gap-4">
                            <span><strong>Type:</strong> {{ currentFile.document_type.replace('_', ' ').toUpperCase() }}</span>
                            <span><strong>Size:</strong> {{ formatFileSize(currentFile.size) }}</span>
                            <span><strong>Format:</strong> {{ currentFile.mime_type }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Keyboard Shortcuts Help -->
                            <div v-if="isImageFile(currentFile.mime_type)" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                <span class="font-medium">Shortcuts:</span>
                                <span class="mx-1">+/-</span> zoom,
                                <span class="mx-1">0</span> reset,
                                <span class="mx-1">F</span> fullscreen,
                                <span class="mx-1">Esc</span> close
                            </div>
                            <Button @click="downloadFile(currentFile)" variant="outline" size="sm">
                                <Download class="h-3 w-3 mr-1" />
                                Download
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </StaffLayout>
</template>

