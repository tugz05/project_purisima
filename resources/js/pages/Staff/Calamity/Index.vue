<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Eye, Filter, AlertTriangle, MapPin, Clock, CheckCircle, AlertCircle, Map } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useFormHandlers } from '@/composables/useFormHandlers';
import { useUtils } from '@/composables/useUtils';

interface CalamityReport {
    id: number;
    calamity_type: string;
    severity: string;
    status: string;
    description: string;
    needs: string[];
    number_of_people: number;
    created_at: string;
    resident: {
        id: number;
        name: string;
        email: string;
    };
    staff?: {
        name: string;
    };
}

interface Props {
    reports: {
        data: CalamityReport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        calamity_type?: string;
        severity?: string;
    };
    statistics: {
        total: number;
        pending: number;
        acknowledged: number;
        in_progress: number;
        assisted: number;
        resolved: number;
        critical: number;
        high: number;
    };
}

const props = defineProps<Props>();

const { formatDateShort } = useUtils();
const { createFilterForm, createDebouncedSearch } = useFormHandlers();

const searchQuery = ref(props.filters.search || '');
const filterForm = createFilterForm(props.filters);

createDebouncedSearch(searchQuery, filterForm, '/staff/calamity');

const applyFilters = () => {
    filterForm.get('/staff/calamity');
};

const clearSearch = () => {
    searchQuery.value = '';
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.calamity_type = 'all';
    filterForm.severity = 'all';
    filterForm.get('/staff/calamity');
};

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

const getSeverityColor = (severity: string) => {
    switch (severity) {
        case 'critical':
            return 'bg-red-100 text-red-800';
        case 'high':
            return 'bg-orange-100 text-orange-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-green-100 text-green-800';
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
    <Head title="Calamity Reports" />

    <StaffLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-orange-600 to-red-600 shadow-xl mb-6 rounded-2xl">
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
                                        <AlertTriangle class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h1 class="text-4xl font-bold">Calamity Reports</h1>
                                        <p class="text-red-100 text-lg mt-1">Emergency response and assistance management</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-sm text-red-100">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                        <span>{{ statistics.pending || 0 }} Pending</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                                        <span>{{ statistics.critical || 0 }} Critical</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-orange-400 rounded-full"></div>
                                        <span>{{ statistics.high || 0 }} High</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                        <span>{{ statistics.resolved || 0 }} Resolved</span>
                                    </div>
                                </div>
                            </div>
                            <Link href="/staff/calamity/map">
                                <Button class="bg-white text-red-600 hover:bg-red-50 shadow-lg">
                                    <Map class="h-4 w-4 mr-2" />
                                    View Map
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <Card class="shadow-lg border-gray-200 mb-6">
                    <CardHeader class="pb-4">
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <Filter class="h-5 w-5" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Search</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search reports..."
                                        class="pl-10 h-10"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Status</label>
                                <Select v-model="filterForm.status" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-10">
                                        <SelectValue placeholder="All Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="acknowledged">Acknowledged</SelectItem>
                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                        <SelectItem value="assisted">Assisted</SelectItem>
                                        <SelectItem value="resolved">Resolved</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Calamity Type</label>
                                <Select v-model="filterForm.calamity_type" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-10">
                                        <SelectValue placeholder="All Types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem value="typhoon">Typhoon</SelectItem>
                                        <SelectItem value="flood">Flood</SelectItem>
                                        <SelectItem value="earthquake">Earthquake</SelectItem>
                                        <SelectItem value="fire">Fire</SelectItem>
                                        <SelectItem value="landslide">Landslide</SelectItem>
                                        <SelectItem value="drought">Drought</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">Severity</label>
                                <Select v-model="filterForm.severity" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-10">
                                        <SelectValue placeholder="All Severity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Severity</SelectItem>
                                        <SelectItem value="critical">Critical</SelectItem>
                                        <SelectItem value="high">High</SelectItem>
                                        <SelectItem value="medium">Medium</SelectItem>
                                        <SelectItem value="low">Low</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700">&nbsp;</label>
                                <Button @click="clearSearch" variant="outline" class="w-full h-10">
                                    Clear Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Reports Table -->
                <Card class="shadow-lg border-gray-200">
                    <CardHeader>
                        <CardTitle>Reports ({{ props.reports.total }})</CardTitle>
                        <CardDescription>
                            Showing {{ props.reports.from }} to {{ props.reports.to }} of {{ props.reports.total }} reports
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-gray-50">
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Resident</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Calamity Type</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Severity</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Status</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Needs</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">People</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Reported</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="report in props.reports.data" :key="report.id" class="hover:bg-gray-50">
                                        <TableCell class="px-4 py-4">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ report.resident.name }}</p>
                                                <p class="text-sm text-gray-500">{{ report.resident.email }}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <p class="text-sm text-gray-900 capitalize">{{ formatCalamityType(report.calamity_type) }}</p>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <Badge :class="getSeverityColor(report.severity)" class="capitalize">
                                                {{ report.severity }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <Badge :class="getStatusColor(report.status)">
                                                <component :is="getStatusIcon(report.status)" class="h-3 w-3 mr-1" />
                                                {{ formatStatus(report.status) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <Badge
                                                    v-for="need in (report.needs || []).slice(0, 2)"
                                                    :key="need"
                                                    variant="outline"
                                                    class="text-xs capitalize"
                                                >
                                                    {{ need }}
                                                </Badge>
                                                <Badge
                                                    v-if="(report.needs || []).length > 2"
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    +{{ (report.needs || []).length - 2 }}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <p class="text-sm text-gray-900">{{ report.number_of_people }}</p>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <p class="text-sm text-gray-600">{{ formatDateShort(report.created_at) }}</p>
                                        </TableCell>
                                        <TableCell class="px-4 py-4 text-right">
                                            <Link :href="`/staff/calamity/${report.id}`">
                                                <Button variant="ghost" size="sm" class="h-8">
                                                    <Eye class="h-4 w-4 mr-1" />
                                                    View
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="props.reports.data.length === 0">
                                        <TableCell colspan="8" class="text-center py-12">
                                            <div class="flex flex-col items-center gap-2">
                                                <AlertTriangle class="h-12 w-12 text-gray-400" />
                                                <p class="text-gray-500 font-medium">No reports found</p>
                                                <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="props.reports.last_page > 1" class="flex items-center justify-between px-4 py-4 border-t border-gray-200 mt-4">
                            <div class="text-sm text-gray-600">
                                Showing {{ props.reports.from }} to {{ props.reports.to }} of {{ props.reports.total }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="props.reports.current_page > 1"
                                    :href="`/staff/calamity?page=${props.reports.current_page - 1}&${new URLSearchParams(filterForm.data() as any).toString()}`"
                                    preserve-state
                                >
                                    <Button variant="outline" size="sm">Previous</Button>
                                </Link>
                                <Link
                                    v-if="props.reports.current_page < props.reports.last_page"
                                    :href="`/staff/calamity?page=${props.reports.current_page + 1}&${new URLSearchParams(filterForm.data() as any).toString()}`"
                                    preserve-state
                                >
                                    <Button variant="outline" size="sm">Next</Button>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StaffLayout>
</template>

