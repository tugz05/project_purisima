<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ documentType.name }}</h1>
                <p class="text-gray-600">{{ documentType.code }}</p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="`/staff/document-types/${documentType.id}/edit`">
                    <Button variant="outline" class="flex items-center gap-2">
                        <Edit class="h-4 w-4" />
                        Edit
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

        <!-- Document Type Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
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
                                <Label class="text-sm font-medium text-gray-700">Code</Label>
                                <p class="text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded-md">{{ documentType.code }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-700">Category</Label>
                                <p class="text-sm text-gray-900">{{ documentType.category || 'Not specified' }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-700">Processing Days</Label>
                                <p class="text-sm text-gray-900">{{ documentType.processing_days }} days</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-700">Sort Order</Label>
                                <p class="text-sm text-gray-900">{{ documentType.sort_order }}</p>
                            </div>
                        </div>

                        <div>
                            <Label class="text-sm font-medium text-gray-700">Description</Label>
                            <p class="text-sm text-gray-900 mt-1">{{ documentType.description || 'No description provided' }}</p>
                        </div>

                        <div v-if="documentType.notes">
                            <Label class="text-sm font-medium text-gray-700">Notes</Label>
                            <p class="text-sm text-gray-900 mt-1">{{ documentType.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Required Documents -->
                <Card v-if="documentType.required_documents && documentType.required_documents.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileCheck class="h-5 w-5" />
                            Required Documents
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2">
                            <li v-for="(doc, index) in documentType.required_documents" :key="index" class="flex items-start gap-2">
                                <CheckCircle class="h-4 w-4 text-green-600 mt-0.5 flex-shrink-0" />
                                <span class="text-sm text-gray-900">{{ doc }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Processing Steps -->
                <Card v-if="documentType.processing_steps && documentType.processing_steps.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Processing Steps
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol class="space-y-3">
                            <li v-for="(step, index) in documentType.processing_steps" :key="index" class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ index + 1 }}
                                </div>
                                <span class="text-sm text-gray-900 pt-0.5">{{ step }}</span>
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status & Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Status & Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Status</span>
                            <Badge :variant="documentType.is_active ? 'default' : 'secondary'">
                                {{ documentType.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Requires Payment</span>
                            <Badge :variant="documentType.requires_payment ? 'default' : 'secondary'">
                                {{ documentType.requires_payment ? 'Yes' : 'No' }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Requires Approval</span>
                            <Badge :variant="documentType.requires_approval ? 'default' : 'secondary'">
                                {{ documentType.requires_approval ? 'Yes' : 'No' }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Fee Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <DollarSign class="h-5 w-5" />
                            Fee Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900">
                                {{ documentType.formatted_fee }}
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ documentType.is_free ? 'Free Service' : 'Processing Fee' }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Statistics
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Requests</span>
                            <span class="text-sm font-medium">{{ documentType.transactions_count || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Created</span>
                            <span class="text-sm font-medium">{{ formatDate(documentType.created_at) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Last Updated</span>
                            <span class="text-sm font-medium">{{ formatDate(documentType.updated_at) }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    ArrowLeft,
    Edit,
    FileText,
    FileCheck,
    Settings,
    Activity,
    DollarSign,
    BarChart3,
    CheckCircle
} from 'lucide-vue-next';

interface DocumentType {
    id: number;
    code: string;
    name: string;
    description?: string;
    fee_amount: number;
    required_documents?: string[];
    processing_steps?: string[];
    processing_days: number;
    is_active: boolean;
    requires_payment: boolean;
    requires_approval: boolean;
    category?: string;
    sort_order: number;
    notes?: string;
    transactions_count?: number;
    created_at: string;
    updated_at: string;
    formatted_fee: string;
    is_free: boolean;
}

interface Props {
    documentType: DocumentType;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

