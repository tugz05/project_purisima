<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, AlertTriangle, MapPin, Clock, CheckCircle, AlertCircle, Users, Heart, Navigation } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useUtils } from '@/composables/useUtils';

interface CalamityReport {
    id: number;
    latitude: number;
    longitude: number;
    address: string;
    location_notes: string;
    calamity_type: string;
    severity: string;
    status: string;
    description: string;
    needs: string[];
    specific_needs: string;
    number_of_people: number;
    has_elderly: boolean;
    has_children: boolean;
    has_pwd: boolean;
    has_pregnant: boolean;
    medical_conditions: string;
    staff_notes: string;
    assistance_provided: string;
    created_at: string;
    acknowledged_at: string;
    assisted_at: string;
    resolved_at: string;
    staff?: {
        name: string;
    };
}

interface Props {
    report: CalamityReport;
}

const props = defineProps<Props>();

const { formatDateShort, formatDateTime } = useUtils();

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending':
            return Clock;
        case 'acknowledged':
            return AlertCircle;
        case 'in_progress':
            return AlertCircle;
        case 'assisted':
            return CheckCircle;
        case 'resolved':
            return CheckCircle;
        default:
            return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'acknowledged':
            return 'bg-blue-100 text-blue-800';
        case 'in_progress':
            return 'bg-indigo-100 text-indigo-800';
        case 'assisted':
            return 'bg-green-100 text-green-800';
        case 'resolved':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatStatus = (status: string): string => {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const formatCalamityType = (type: string): string => {
    return type.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};
</script>

<template>
    <Head title="Calamity Report Details" />

    <ResidentLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-4xl">
                <!-- Header -->
                <div class="mb-6">
                    <Link href="/resident/calamity">
                        <Button variant="ghost" size="sm" class="mb-4">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Reports
                        </Button>
                    </Link>

                    <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-orange-600 to-red-600 shadow-xl rounded-2xl">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                        <AlertTriangle class="h-8 w-8 text-white" />
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold text-white">Report Details</h1>
                                        <p class="text-red-100 text-lg mt-1">{{ formatCalamityType(props.report.calamity_type) }}</p>
                                    </div>
                                </div>
                                <Badge :class="getStatusColor(props.report.status)" class="text-base px-4 py-2">
                                    <component :is="getStatusIcon(props.report.status)" class="h-4 w-4 mr-1" />
                                    {{ formatStatus(props.report.status) }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Calamity Information -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <AlertTriangle class="h-5 w-5 text-red-600" />
                                    Calamity Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-1">Type</p>
                                        <p class="text-base text-gray-900 capitalize">{{ formatCalamityType(props.report.calamity_type) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-1">Severity</p>
                                        <Badge class="capitalize">{{ props.report.severity }}</Badge>
                                    </div>
                                </div>
                                <div v-if="props.report.description">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Description</p>
                                    <p class="text-base text-gray-900">{{ props.report.description }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Location Information -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <MapPin class="h-5 w-5 text-blue-600" />
                                    Location
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="props.report.address">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Address</p>
                                    <p class="text-base text-gray-900">{{ props.report.address }}</p>
                                </div>
                                <div v-if="props.report.latitude && props.report.longitude">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Coordinates</p>
                                    <p class="text-base text-gray-900 font-mono">
                                        {{ props.report.latitude.toFixed(6) }}, {{ props.report.longitude.toFixed(6) }}
                                    </p>
                                </div>
                                <div v-if="props.report.location_notes">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Location Notes</p>
                                    <p class="text-base text-gray-900">{{ props.report.location_notes }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Needs -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Heart class="h-5 w-5 text-pink-600" />
                                    Needs
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="props.report.needs && props.report.needs.length > 0">
                                    <div class="flex flex-wrap gap-2">
                                        <Badge
                                            v-for="need in props.report.needs"
                                            :key="need"
                                            variant="outline"
                                            class="capitalize"
                                        >
                                            {{ need }}
                                        </Badge>
                                    </div>
                                </div>
                                <div v-if="props.report.specific_needs">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Specific Needs</p>
                                    <p class="text-base text-gray-900">{{ props.report.specific_needs }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Staff Response -->
                        <Card v-if="props.report.staff_notes || props.report.assistance_provided" class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <CheckCircle class="h-5 w-5 text-green-600" />
                                    Staff Response
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="props.report.staff_notes">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Staff Notes</p>
                                    <p class="text-base text-gray-900">{{ props.report.staff_notes }}</p>
                                </div>
                                <div v-if="props.report.assistance_provided">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Assistance Provided</p>
                                    <p class="text-base text-gray-900">{{ props.report.assistance_provided }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- People Information -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-indigo-600" />
                                    People
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">Number of People</p>
                                    <p class="text-base text-gray-900 font-semibold">{{ props.report.number_of_people }}</p>
                                </div>
                                <div class="space-y-2">
                                    <div v-if="props.report.has_elderly" class="flex items-center gap-2 text-sm text-gray-700">
                                        <Users class="h-4 w-4" />
                                        <span>Elderly present</span>
                                    </div>
                                    <div v-if="props.report.has_children" class="flex items-center gap-2 text-sm text-gray-700">
                                        <Users class="h-4 w-4" />
                                        <span>Children present</span>
                                    </div>
                                    <div v-if="props.report.has_pwd" class="flex items-center gap-2 text-sm text-gray-700">
                                        <Users class="h-4 w-4" />
                                        <span>PWD present</span>
                                    </div>
                                    <div v-if="props.report.has_pregnant" class="flex items-center gap-2 text-sm text-gray-700">
                                        <Users class="h-4 w-4" />
                                        <span>Pregnant person present</span>
                                    </div>
                                </div>
                                <div v-if="props.report.medical_conditions">
                                    <p class="text-sm font-medium text-gray-500 mb-1">Medical Conditions</p>
                                    <p class="text-sm text-gray-900">{{ props.report.medical_conditions }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Timeline -->
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Clock class="h-5 w-5 text-gray-600" />
                                    Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Reported</p>
                                    <p class="text-sm text-gray-900">{{ formatDateTime(props.report.created_at) }}</p>
                                </div>
                                <div v-if="props.report.acknowledged_at">
                                    <p class="text-xs font-medium text-gray-500">Acknowledged</p>
                                    <p class="text-sm text-gray-900">{{ formatDateTime(props.report.acknowledged_at) }}</p>
                                </div>
                                <div v-if="props.report.assisted_at">
                                    <p class="text-xs font-medium text-gray-500">Assisted</p>
                                    <p class="text-sm text-gray-900">{{ formatDateTime(props.report.assisted_at) }}</p>
                                </div>
                                <div v-if="props.report.resolved_at">
                                    <p class="text-xs font-medium text-gray-500">Resolved</p>
                                    <p class="text-sm text-gray-900">{{ formatDateTime(props.report.resolved_at) }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Assigned Staff -->
                        <Card v-if="props.report.staff" class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-blue-600" />
                                    Assigned Staff
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-base text-gray-900 font-medium">{{ props.report.staff.name }}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>

