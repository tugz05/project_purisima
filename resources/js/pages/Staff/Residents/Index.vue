<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Eye, Filter, Users, UserCheck, UserX, MapPin, Phone, Mail, Calendar, FileText, User } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useFormHandlers } from '@/composables/useFormHandlers';
import { useUtils } from '@/composables/useUtils';

interface Resident {
    id: number;
    name: string;
    email: string;
    first_name?: string;
    middle_name?: string;
    last_name?: string;
    phone?: string;
    purok?: string;
    barangay?: string;
    municipality?: string;
    province?: string;
    birth_date?: string;
    sex?: string;
    civil_status?: string;
    occupation?: string;
    photo_url?: string;
    profile_completed_at?: string;
    created_at: string;
    transactions_count: number;
}

interface Props {
    residents: {
        data: Resident[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        purok?: string;
        profile_completed?: string;
    };
    statistics: {
        total: number;
        profile_completed: number;
        profile_incomplete: number;
        with_transactions: number;
    };
    puroks: string[];
}

const props = defineProps<Props>();

// Composables
const { formatDateShort } = useUtils();
const { createFilterForm, createDebouncedSearch } = useFormHandlers();

// State
const searchQuery = ref(props.filters.search || '');
const filterForm = createFilterForm(props.filters);
const profileModalOpen = ref(false);
const selectedResident = ref<Resident | null>(null);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Residents', href: '/staff/residents' },
];

// Debounced search
createDebouncedSearch(searchQuery, filterForm, '/staff/residents');

// Methods
const applyFilters = () => {
    filterForm.get('/staff/residents');
};

const clearSearch = () => {
    searchQuery.value = '';
    filterForm.search = '';
    filterForm.purok = 'all';
    filterForm.profile_completed = 'all';
    filterForm.get('/staff/residents');
};

const getFullName = (resident: Resident): string => {
    if (resident.first_name || resident.last_name) {
        const parts = [resident.first_name, resident.middle_name, resident.last_name].filter(Boolean);
        return parts.join(' ');
    }
    return resident.name || 'N/A';
};

const getAddress = (resident: Resident): string => {
    const parts = [];
    if (resident.purok) parts.push(`Purok ${resident.purok}`);
    if (resident.barangay) parts.push(resident.barangay);
    if (resident.municipality) parts.push(resident.municipality);
    if (resident.province) parts.push(resident.province);
    return parts.length > 0 ? parts.join(', ') : 'Address not provided';
};

const getAge = (birthDate?: string): number | null => {
    if (!birthDate) return null;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};

const formatBirthDate = (dateString?: string): string => {
    if (!dateString) return '';
    // Parse the date and format only the date part (no time)
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const openProfileModal = (resident: Resident) => {
    selectedResident.value = resident;
    profileModalOpen.value = true;
};

const closeProfileModal = () => {
    profileModalOpen.value = false;
    selectedResident.value = null;
};
</script>

<template>
    <Head title="Residents" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Enhanced Header with Gradient -->
                <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 shadow-xl mb-6 rounded-2xl">
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
                                        <Users class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <h1 class="text-4xl font-bold">Residents</h1>
                                        <p class="text-blue-100 text-lg mt-1">View and manage all registered residents</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-sm text-blue-100">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                        <span>{{ statistics.profile_completed || 0 }} Completed Profiles</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                        <span>{{ statistics.profile_incomplete || 0 }} Incomplete</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                        <span>{{ statistics.with_transactions || 0 }} With Transactions</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-white/40 rounded-full"></div>
                                        <span>{{ statistics.total || 0 }} Total Residents</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="mb-6">
                    <Card class="shadow-lg border-gray-200">
                        <CardHeader class="pb-4">
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Filter class="h-5 w-5" />
                                Filters
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Search -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            v-model="searchQuery"
                                            placeholder="Search residents..."
                                            class="pl-10 h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>

                                <!-- Purok Filter -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Purok</label>
                                    <Select v-model="filterForm.purok" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                            <SelectValue placeholder="All Puroks" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Puroks</SelectItem>
                                            <SelectItem v-for="purok in puroks" :key="purok" :value="purok">
                                                Purok {{ purok }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Profile Completion Filter -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Profile Status</label>
                                    <Select v-model="filterForm.profile_completed" @update:model-value="applyFilters">
                                        <SelectTrigger class="h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                            <SelectValue placeholder="All Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Status</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                            <SelectItem value="incomplete">Incomplete</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Clear Filters -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">&nbsp;</label>
                                    <Button @click="clearSearch" variant="outline" class="w-full h-10 border-gray-200 hover:bg-gray-50">
                                        Clear Filters
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Residents Table -->
                <Card class="shadow-lg border-gray-200">
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span>Residents ({{ props.residents.total }})</span>
                        </CardTitle>
                        <CardDescription>
                            Showing {{ props.residents.from }} to {{ props.residents.to }} of {{ props.residents.total }} residents
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-gray-50">
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Name</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Contact</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Address</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Age</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Transactions</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Profile Status</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3">Registered</TableHead>
                                        <TableHead class="font-semibold text-gray-900 px-4 py-3 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="resident in props.residents.data" :key="resident.id" class="hover:bg-gray-50">
                                        <TableCell class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <div class="font-semibold text-gray-900">
                                                    {{ getFullName(resident) }}
                                                </div>
                                                <div v-if="resident.email" class="text-sm text-gray-500 flex items-center gap-1 mt-1">
                                                    <Mail class="h-3 w-3" />
                                                    {{ resident.email }}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div v-if="resident.phone" class="flex items-center gap-1 text-sm text-gray-600">
                                                <Phone class="h-4 w-4" />
                                                {{ resident.phone }}
                                            </div>
                                            <span v-else class="text-sm text-gray-400">Not provided</span>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div class="flex items-start gap-1 text-sm text-gray-600">
                                                <MapPin class="h-4 w-4 mt-0.5 flex-shrink-0" />
                                                <span>{{ getAddress(resident) }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div class="text-sm text-gray-600">
                                                <span v-if="getAge(resident.birth_date) !== null">
                                                    {{ getAge(resident.birth_date) }} years
                                                </span>
                                                <span v-else class="text-gray-400">N/A</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                <FileText class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ resident.transactions_count || 0 }}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <Badge
                                                v-if="resident.profile_completed_at"
                                                class="bg-green-100 text-green-800 hover:bg-green-200"
                                            >
                                                <UserCheck class="h-3 w-3 mr-1" />
                                                Completed
                                            </Badge>
                                            <Badge
                                                v-else
                                                class="bg-yellow-100 text-yellow-800 hover:bg-yellow-200"
                                            >
                                                <UserX class="h-3 w-3 mr-1" />
                                                Incomplete
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="px-4 py-4">
                                            <div class="text-sm text-gray-600">
                                                {{ formatDateShort(resident.created_at) }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-4 py-4 text-right">
                                            <Button variant="ghost" size="sm" class="h-8" @click="openProfileModal(resident)">
                                                <Eye class="h-4 w-4 mr-1" />
                                                View Profile
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="props.residents.data.length === 0">
                                        <TableCell colspan="8" class="text-center py-12">
                                            <div class="flex flex-col items-center gap-2">
                                                <Users class="h-12 w-12 text-gray-400" />
                                                <p class="text-gray-500 font-medium">No residents found</p>
                                                <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="props.residents.last_page > 1" class="flex items-center justify-between px-4 py-4 border-t border-gray-200 mt-4">
                            <div class="text-sm text-gray-600">
                                Showing {{ props.residents.from }} to {{ props.residents.to }} of {{ props.residents.total }} results
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="props.residents.current_page > 1"
                                    :href="`/staff/residents?page=${props.residents.current_page - 1}&${new URLSearchParams(filterForm.data() as any).toString()}`"
                                    preserve-state
                                >
                                    <Button variant="outline" size="sm">Previous</Button>
                                </Link>
                                <Link
                                    v-if="props.residents.current_page < props.residents.last_page"
                                    :href="`/staff/residents?page=${props.residents.current_page + 1}&${new URLSearchParams(filterForm.data() as any).toString()}`"
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

        <!-- Profile Modal -->
        <Dialog :open="profileModalOpen" @update:open="closeProfileModal">
            <DialogContent class="max-w-3xl max-h-[90vh] p-0 flex flex-col overflow-hidden">
                <div v-if="selectedResident" class="flex flex-col h-full max-h-[90vh]">
                    <!-- Professional Header with Gradient -->
                    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 flex-shrink-0">
                        <div class="absolute inset-0 bg-black/5"></div>
                        <div class="absolute inset-0">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"></div>
                            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-24 -translate-x-24"></div>
                        </div>
                        <DialogHeader class="relative p-6 pb-8">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                    <User class="h-6 w-6 text-white" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle class="text-2xl font-bold text-white">Resident Profile</DialogTitle>
                                    <p class="text-blue-100 text-sm mt-1">View resident personal information</p>
                                </div>
                            </div>
                        </DialogHeader>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="flex-1 overflow-y-auto overscroll-contain bg-gradient-to-br from-gray-50 to-blue-50/30">
                        <!-- Profile Photo and Header Section -->
                        <div class="relative mb-6 pt-8">
                            <div class="flex flex-col items-center px-6">
                                <!-- Profile Photo with Professional Styling -->
                                <div class="relative mb-4 z-10">
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full blur-xl opacity-30 scale-110 -z-10"></div>
                                    <div v-if="selectedResident.photo_url" class="relative w-36 h-36 rounded-full overflow-hidden border-4 border-white shadow-2xl ring-4 ring-blue-100">
                                        <img :src="selectedResident.photo_url" :alt="getFullName(selectedResident)" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="relative w-36 h-36 rounded-full bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 flex items-center justify-center border-4 border-white shadow-2xl ring-4 ring-blue-100">
                                        <User class="h-20 w-20 text-white" />
                                    </div>
                                </div>
                                
                                <!-- Name and Email -->
                                <div class="text-center space-y-2">
                                    <h2 class="text-3xl font-bold text-gray-900">{{ getFullName(selectedResident) }}</h2>
                                    <div class="flex items-center justify-center gap-2 text-gray-600">
                                        <Mail class="h-4 w-4" />
                                        <p class="text-base">{{ selectedResident.email }}</p>
                                    </div>
                                    <div class="pt-2">
                                        <Badge
                                            v-if="selectedResident.profile_completed_at"
                                            class="bg-green-500 text-white hover:bg-green-600 shadow-md px-4 py-1.5"
                                        >
                                            <UserCheck class="h-4 w-4 mr-1.5" />
                                            Profile Completed
                                        </Badge>
                                        <Badge
                                            v-else
                                            class="bg-yellow-500 text-white hover:bg-yellow-600 shadow-md px-4 py-1.5"
                                        >
                                            <UserX class="h-4 w-4 mr-1.5" />
                                            Profile Incomplete
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Cards -->
                        <div class="px-6 space-y-6 pb-6">

                        <!-- Incomplete Profile Notice -->
                        <Card v-if="!selectedResident.profile_completed_at" class="shadow-lg border-yellow-200 bg-gradient-to-r from-yellow-50 to-amber-50">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-4">
                                    <div class="p-3 bg-yellow-100 rounded-lg flex-shrink-0">
                                        <UserX class="h-6 w-6 text-yellow-600" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Profile Incomplete</h3>
                                        <p class="text-sm text-gray-600 mb-4">This resident's profile is not yet complete. Some information may be missing.</p>
                                        <div class="space-y-2">
                                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">Missing Information:</p>
                                            <ul class="space-y-1.5">
                                                <li v-if="!selectedResident.phone" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Phone Number
                                                </li>
                                                <li v-if="!selectedResident.birth_date" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Birth Date
                                                </li>
                                                <li v-if="!selectedResident.sex" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Sex
                                                </li>
                                                <li v-if="!selectedResident.civil_status" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Civil Status
                                                </li>
                                                <li v-if="!selectedResident.occupation" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Occupation
                                                </li>
                                                <li v-if="!selectedResident.purok" class="flex items-center gap-2 text-sm text-gray-600">
                                                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                                                    Address (Purok)
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Personal Information Card -->
                        <Card class="shadow-lg border-gray-200 bg-white">
                            <CardHeader class="pb-4 border-b border-gray-100">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <User class="h-4 w-4 text-blue-600" />
                                    </div>
                                    Personal Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="pt-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Phone -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Phone Number</Label>
                                        <div v-if="selectedResident.phone" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="p-2 bg-blue-100 rounded-lg">
                                                <Phone class="h-4 w-4 text-blue-600" />
                                            </div>
                                            <p class="text-base font-medium text-gray-900">{{ selectedResident.phone }}</p>
                                        </div>
                                        <div v-else class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <div class="p-2 bg-gray-100 rounded-lg opacity-50">
                                                <Phone class="h-4 w-4 text-gray-400" />
                                            </div>
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>

                                    <!-- Age -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Age</Label>
                                        <div v-if="getAge(selectedResident.birth_date) !== null" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="p-2 bg-indigo-100 rounded-lg">
                                                <Calendar class="h-4 w-4 text-indigo-600" />
                                            </div>
                                            <p class="text-base font-medium text-gray-900">{{ getAge(selectedResident.birth_date) }} years old</p>
                                        </div>
                                        <div v-else class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <div class="p-2 bg-gray-100 rounded-lg opacity-50">
                                                <Calendar class="h-4 w-4 text-gray-400" />
                                            </div>
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>

                                    <!-- Birth Date -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Birth Date</Label>
                                        <div v-if="selectedResident.birth_date" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="p-2 bg-purple-100 rounded-lg">
                                                <Calendar class="h-4 w-4 text-purple-600" />
                                            </div>
                                            <p class="text-base font-medium text-gray-900">{{ formatBirthDate(selectedResident.birth_date) }}</p>
                                        </div>
                                        <div v-else class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <div class="p-2 bg-gray-100 rounded-lg opacity-50">
                                                <Calendar class="h-4 w-4 text-gray-400" />
                                            </div>
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>

                                    <!-- Sex -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sex</Label>
                                        <div v-if="selectedResident.sex" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-base font-medium text-gray-900 capitalize">{{ selectedResident.sex }}</p>
                                        </div>
                                        <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>

                                    <!-- Civil Status -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Civil Status</Label>
                                        <div v-if="selectedResident.civil_status" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-base font-medium text-gray-900 capitalize">{{ selectedResident.civil_status }}</p>
                                        </div>
                                        <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>

                                    <!-- Occupation -->
                                    <div class="space-y-2 md:col-span-2">
                                        <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Occupation</Label>
                                        <div v-if="selectedResident.occupation" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-base font-medium text-gray-900">{{ selectedResident.occupation }}</p>
                                        </div>
                                        <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                            <p class="text-sm text-gray-400 italic">Not provided</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Address Information Card -->
                        <Card class="shadow-lg border-gray-200 bg-white">
                            <CardHeader class="pb-4 border-b border-gray-100">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <div class="p-2 bg-teal-100 rounded-lg">
                                        <MapPin class="h-4 w-4 text-teal-600" />
                                    </div>
                                    Address Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="pt-6">
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Full Address</Label>
                                    <div v-if="selectedResident.purok" class="flex items-start gap-3 p-4 bg-gradient-to-r from-teal-50 to-blue-50 rounded-lg border border-teal-200">
                                        <div class="p-2 bg-teal-100 rounded-lg flex-shrink-0 mt-0.5">
                                            <MapPin class="h-5 w-5 text-teal-600" />
                                        </div>
                                        <p class="text-base font-medium text-gray-900 leading-relaxed">{{ getAddress(selectedResident) }}</p>
                                    </div>
                                    <div v-else class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                        <div class="p-2 bg-gray-100 rounded-lg flex-shrink-0 mt-0.5 opacity-50">
                                            <MapPin class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <p class="text-sm text-gray-400 italic">Address not provided</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </StaffLayout>
</template>

