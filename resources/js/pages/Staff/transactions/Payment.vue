<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ArrowLeft, DollarSign, CreditCard, FileText, Upload, AlertCircle, CheckCircle, XCircle, Clock } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
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
    resident: {
        id: number;
        name: string;
        email: string;
        phone: string;
    };
    documentType: {
        id: number;
        name: string;
        fee_amount: number;
    };
    paymentVerifier: {
        id: number;
        name: string;
    };
}

interface Props {
    transaction: Transaction;
    paymentMethods: Record<string, string>;
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Transactions', href: '/staff/transactions' },
    { title: `Transaction ${props.transaction.transaction_id}`, href: `/staff/transactions/${props.transaction.id}` },
    { title: 'Payment Processing', href: `/staff/transactions/${props.transaction.id}/payment` },
]);

// Form for processing payment
const paymentForm = useForm({
    payment_method: '',
    amount_paid: props.transaction.fee_amount,
    payment_reference: '',
    payment_notes: '',
    payment_proof: [] as File[],
});

// Form for marking payment as failed
const failedForm = useForm({
    reason: '',
});

// Form for processing refund
const refundForm = useForm({
    reason: '',
});

// Dialog states
const showFailedDialog = ref(false);
const showRefundDialog = ref(false);

// File upload handling
const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        paymentForm.payment_proof = Array.from(target.files);
    }
};

// Remove uploaded file
const removeFile = (index: number) => {
    paymentForm.payment_proof.splice(index, 1);
};

// Submit payment processing
const submitPayment = () => {
    paymentForm.post(`/staff/transactions/${props.transaction.id}/payment`, {
        onSuccess: () => {
            toast.success('Payment processed successfully!');
        },
        onError: (errors) => {
            console.error('Payment processing failed:', errors);
            toast.error('Failed to process payment');
        }
    });
};

// Submit payment failure
const submitFailed = () => {
    failedForm.post(`/staff/transactions/${props.transaction.id}/payment/failed`, {
        onSuccess: () => {
            toast.success('Payment marked as failed');
            showFailedDialog.value = false;
        },
        onError: (errors) => {
            console.error('Failed to mark payment as failed:', errors);
            toast.error('Failed to mark payment as failed');
        }
    });
};

// Submit refund
const submitRefund = () => {
    refundForm.post(`/staff/transactions/${props.transaction.id}/payment/refund`, {
        onSuccess: () => {
            toast.success('Refund processed successfully');
            showRefundDialog.value = false;
        },
        onError: (errors) => {
            console.error('Failed to process refund:', errors);
            toast.error('Failed to process refund');
        }
    });
};

// Reset payment status
const resetPayment = () => {
    if (confirm('Are you sure you want to reset the payment status? This will clear all payment information.')) {
        useForm({}).post(`/staff/transactions/${props.transaction.id}/payment/reset`, {
            onSuccess: () => {
                toast.success('Payment status reset successfully');
            },
            onError: (errors) => {
                console.error('Failed to reset payment status:', errors);
                toast.error('Failed to reset payment status');
            }
        });
    }
};

// Computed properties
const paymentStatusColor = computed(() => {
    switch (props.transaction.payment_status) {
        case 'paid': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
});

const paymentStatusIcon = computed(() => {
    switch (props.transaction.payment_status) {
        case 'paid': return CheckCircle;
        case 'pending': return Clock;
        case 'failed': return XCircle;
        case 'refunded': return AlertCircle;
        default: return Clock;
    }
});

const canProcessPayment = computed(() => {
    return props.transaction.payment_status === 'pending' || props.transaction.payment_status === 'failed';
});

const canMarkFailed = computed(() => {
    return props.transaction.payment_status === 'pending';
});

const canProcessRefund = computed(() => {
    return props.transaction.payment_status === 'paid';
});

const canResetPayment = computed(() => {
    return props.transaction.payment_status !== 'pending';
});

// Helper functions for formatting
const formatCurrency = (amount: any) => {
    const num = Number(amount) || 0;
    return num.toFixed(2);
};
</script>

<template>
    <Head title="Payment Processing" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-teal-50">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-4">
                        <Link :href="`/staff/transactions/${transaction.id}`" class="text-blue-600 hover:text-blue-700">
                            <ArrowLeft class="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Payment Processing</h1>
                            <p class="text-lg text-gray-600">Process payment for transaction {{ transaction.transaction_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Transaction Details -->
                    <div class="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Transaction Details
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="space-y-6">
                                    <div>
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Transaction ID</Label>
                                        <p class="text-lg font-semibold">{{ transaction.transaction_id }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Resident</Label>
                                        <p class="text-lg font-semibold">{{ transaction.resident.name }}</p>
                                        <p class="text-sm text-gray-600 mt-1">{{ transaction.resident.email }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Document Type</Label>
                                        <p class="text-lg font-semibold">{{ transaction.documentType?.name || 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Required Fee</Label>
                                        <p class="text-2xl font-bold text-green-600">₱{{ formatCurrency(transaction.fee_amount) }}</p>
                                    </div>

                                    <div>
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Payment Status</Label>
                                        <div class="flex items-center gap-2">
                                            <component :is="paymentStatusIcon" class="h-4 w-4" />
                                            <Badge :class="paymentStatusColor" class="capitalize">
                                                {{ transaction.payment_status }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div v-if="transaction.payment_status === 'paid'">
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Amount Paid</Label>
                                        <p class="text-lg font-semibold text-green-600">₱{{ formatCurrency(transaction.amount_paid) }}</p>
                                    </div>

                                    <div v-if="transaction.payment_method">
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Payment Method</Label>
                                        <p class="text-lg font-semibold">{{ paymentMethods[transaction.payment_method] || transaction.payment_method }}</p>
                                    </div>

                                    <div v-if="transaction.receipt_number">
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Receipt Number</Label>
                                        <p class="text-lg font-semibold">{{ transaction.receipt_number }}</p>
                                    </div>

                                    <div v-if="transaction.paymentVerifier">
                                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Verified By</Label>
                                        <p class="text-lg font-semibold">{{ transaction.paymentVerifier.name }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Payment Processing Form -->
                    <div class="lg:col-span-2">
                        <Card v-if="canProcessPayment">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <CreditCard class="h-5 w-5" />
                                    Process Payment
                                </CardTitle>
                                <CardDescription>
                                    Enter payment details and upload proof of payment
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitPayment" class="space-y-6">
                                    <!-- Payment Method -->
                                    <div>
                                        <Label for="payment_method" class="mb-3 block">Payment Method *</Label>
                                        <Select v-model="paymentForm.payment_method" required>
                                            <SelectTrigger class="mb-2">
                                                <SelectValue placeholder="Select payment method" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                                    {{ label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="paymentForm.errors.payment_method" class="text-red-600 text-sm mt-2">
                                            {{ paymentForm.errors.payment_method }}
                                        </div>
                                    </div>

                                    <!-- Amount Paid -->
                                    <div>
                                        <Label for="amount_paid" class="mb-3 block">Amount Paid *</Label>
                                        <Input
                                            id="amount_paid"
                                            v-model.number="paymentForm.amount_paid"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="text-lg font-semibold mb-2"
                                        />
                                        <p class="text-sm text-gray-600 mb-2">
                                            Required fee: ₱{{ formatCurrency(transaction.fee_amount) }}
                                        </p>
                                        <div v-if="paymentForm.errors.amount_paid" class="text-red-600 text-sm mt-2">
                                            {{ paymentForm.errors.amount_paid }}
                                        </div>
                                    </div>

                                    <!-- Payment Reference -->
                                    <div>
                                        <Label for="payment_reference" class="mb-3 block">Payment Reference</Label>
                                        <Input
                                            id="payment_reference"
                                            v-model="paymentForm.payment_reference"
                                            placeholder="e.g., GCash reference number, check number"
                                            class="mb-2"
                                        />
                                        <div v-if="paymentForm.errors.payment_reference" class="text-red-600 text-sm mt-2">
                                            {{ paymentForm.errors.payment_reference }}
                                        </div>
                                    </div>

                                    <!-- Payment Notes -->
                                    <div>
                                        <Label for="payment_notes" class="mb-3 block">Payment Notes</Label>
                                        <Textarea
                                            id="payment_notes"
                                            v-model="paymentForm.payment_notes"
                                            placeholder="Additional notes about the payment"
                                            rows="3"
                                            class="mb-2"
                                        />
                                        <div v-if="paymentForm.errors.payment_notes" class="text-red-600 text-sm mt-2">
                                            {{ paymentForm.errors.payment_notes }}
                                        </div>
                                    </div>

                                    <!-- Payment Proof Upload -->
                                    <div>
                                        <Label for="payment_proof" class="mb-3 block">Payment Proof (Optional)</Label>
                                        <Input
                                            id="payment_proof"
                                            type="file"
                                            multiple
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            @change="handleFileUpload"
                                            class="mb-3"
                                        />
                                        <p class="text-sm text-gray-600 mb-3">
                                            Upload screenshots, receipts, or other proof of payment (Max 5MB per file)
                                        </p>

                                        <!-- Uploaded Files List -->
                                        <div v-if="paymentForm.payment_proof.length > 0" class="space-y-2 mb-3">
                                            <div v-for="(file, index) in paymentForm.payment_proof" :key="index"
                                                 class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                                <div class="flex items-center gap-2">
                                                    <FileText class="h-4 w-4 text-gray-500" />
                                                    <span class="text-sm">{{ file.name }}</span>
                                                    <span class="text-xs text-gray-500">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="removeFile(index)"
                                                    class="text-red-600 hover:text-red-700"
                                                >
                                                    <XCircle class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        <div v-if="paymentForm.errors.payment_proof" class="text-red-600 text-sm mt-2">
                                            {{ paymentForm.errors.payment_proof }}
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="flex gap-4 pt-4">
                                        <Button
                                            type="submit"
                                            :disabled="paymentForm.processing"
                                            class="flex-1 bg-green-600 hover:bg-green-700"
                                        >
                                            <CheckCircle class="h-4 w-4 mr-2" />
                                            {{ paymentForm.processing ? 'Processing...' : 'Process Payment' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <!-- Payment Actions -->
                        <div class="space-y-4">
                            <!-- Mark as Failed -->
                            <Card v-if="canMarkFailed">
                                <CardContent class="pt-6">
                                    <Dialog v-model:open="showFailedDialog">
                                        <DialogTrigger as-child>
                                            <Button variant="outline" class="w-full text-red-600 border-red-200 hover:bg-red-50">
                                                <XCircle class="h-4 w-4 mr-2" />
                                                Mark Payment as Failed
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Mark Payment as Failed</DialogTitle>
                                            </DialogHeader>
                                            <form @submit.prevent="submitFailed" class="space-y-4">
                                                <div>
                                                    <Label for="failed_reason" class="mb-3 block">Reason for Failure *</Label>
                                                    <Textarea
                                                        id="failed_reason"
                                                        v-model="failedForm.reason"
                                                        placeholder="Explain why the payment failed"
                                                        rows="3"
                                                        required
                                                        class="mb-2"
                                                    />
                                                </div>
                                                <div class="flex gap-2">
                                                    <Button type="submit" :disabled="failedForm.processing" class="flex-1">
                                                        {{ failedForm.processing ? 'Processing...' : 'Mark as Failed' }}
                                                    </Button>
                                                    <Button type="button" variant="outline" @click="showFailedDialog = false">
                                                        Cancel
                                                    </Button>
                                                </div>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                </CardContent>
                            </Card>

                            <!-- Process Refund -->
                            <Card v-if="canProcessRefund">
                                <CardContent class="pt-6">
                                    <Dialog v-model:open="showRefundDialog">
                                        <DialogTrigger as-child>
                                            <Button variant="outline" class="w-full text-blue-600 border-blue-200 hover:bg-blue-50">
                                                <AlertCircle class="h-4 w-4 mr-2" />
                                                Process Refund
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Process Refund</DialogTitle>
                                            </DialogHeader>
                                            <form @submit.prevent="submitRefund" class="space-y-4">
                                                <div>
                                                    <Label for="refund_reason" class="mb-3 block">Refund Reason *</Label>
                                                    <Textarea
                                                        id="refund_reason"
                                                        v-model="refundForm.reason"
                                                        placeholder="Explain why the refund is being processed"
                                                        rows="3"
                                                        required
                                                        class="mb-2"
                                                    />
                                                </div>
                                                <div class="flex gap-2">
                                                    <Button type="submit" :disabled="refundForm.processing" class="flex-1">
                                                        {{ refundForm.processing ? 'Processing...' : 'Process Refund' }}
                                                    </Button>
                                                    <Button type="button" variant="outline" @click="showRefundDialog = false">
                                                        Cancel
                                                    </Button>
                                                </div>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                </CardContent>
                            </Card>

                            <!-- Reset Payment -->
                            <Card v-if="canResetPayment">
                                <CardContent class="pt-6">
                                    <Button
                                        variant="outline"
                                        class="w-full text-gray-600 border-gray-200 hover:bg-gray-50"
                                        @click="resetPayment"
                                    >
                                        <Clock class="h-4 w-4 mr-2" />
                                        Reset Payment Status
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
