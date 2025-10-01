<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Eye, Edit, Mail, Phone, Calendar, MapPin, User, Briefcase, FileText } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useUtils } from '@/composables/useUtils';
import { Link } from '@inertiajs/vue3';

interface BarangayOfficial {
    id: number;
    first_name: string;
    last_name: string;
    middle_name?: string;
    suffix?: string;
    position: string;
    email?: string;
    phone?: string;
    address?: string;
    birth_date?: string;
    gender?: string;
    civil_status?: string;
    photo?: string;
    biography?: string;
    term_start?: string;
    term_end?: string;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    official: BarangayOfficial;
}

const props = defineProps<Props>();

// Composables
const { createBreadcrumbs } = useBreadcrumbs();
const { formatDateShort, formatDateTime } = useUtils();

// Breadcrumbs
const breadcrumbs = createBreadcrumbs([
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Barangay Officials', href: '/staff/barangay-officials' },
    { title: `${props.official.first_name} ${props.official.last_name}`, href: `/staff/barangay-officials/${props.official.id}` }
]);

const getStatusColor = (isActive: boolean) => {
    return isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
};

const getPositionColor = (position: string) => {
    const positionColors: Record<string, string> = {
        'Captain': 'bg-blue-100 text-blue-800',
        'Secretary': 'bg-purple-100 text-purple-800',
        'Treasurer': 'bg-green-100 text-green-800',
        'Councilor': 'bg-orange-100 text-orange-800',
        'SK Chairman': 'bg-pink-100 text-pink-800',
    };

    return positionColors[position] || 'bg-gray-100 text-gray-800';
};

const getFullName = () => {
    let name = props.official.first_name;

    if (props.official.middle_name) {
        name += ' ' + props.official.middle_name;
    }

    name += ' ' + props.official.last_name;

    if (props.official.suffix) {
        name += ' ' + props.official.suffix;
    }

    return name;
};
</script>

<template>
    <Head :title="`${getFullName()} - Barangay Official`" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-4xl">
                <!-- Header Section -->
                <div class="mb-4 md:mb-6">
                    <div class="space-y-1">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                            {{ getFullName() }}
                        </h1>
                        <p class="text-base md:text-lg text-gray-600 font-medium">Barangay Official Details</p>
                    </div>
                </div>

                <!-- View Sheet -->
                <Sheet :open="true" @update:open="() => {}">
                    <SheetContent class="p-0 flex flex-col h-full overflow-y-auto" style="width: 100%; min-width: 700px; max-width: 900px;">
                        <SheetHeader class="p-6 pb-4 border-b border-gray-200">
                            <SheetTitle class="flex items-center gap-2 text-xl font-semibold">
                                <Eye class="h-5 w-5 text-blue-600" />
                                {{ getFullName() }}
                            </SheetTitle>
                            <SheetDescription class="text-gray-600">
                                View official's complete information and details
                            </SheetDescription>
                        </SheetHeader>

                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                            <!-- Official Header -->
                            <div class="text-center space-y-4 pb-6 border-b border-gray-200">
                                <!-- Photo -->
                                <div class="flex justify-center">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200">
                                        <img
                                            v-if="props.official.photo"
                                            :src="`/storage/${props.official.photo}`"
                                            :alt="getFullName()"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center bg-gray-300">
                                            <User class="h-12 w-12 text-gray-500" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Name and Position -->
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">{{ getFullName() }}</h2>
                                    <div class="flex items-center justify-center gap-2 mt-2">
                                        <Badge :class="getPositionColor(props.official.position)" class="text-sm font-medium">
                                            {{ props.official.position }}
                                        </Badge>
                                        <Badge :class="getStatusColor(props.official.is_active)" class="text-sm">
                                            {{ props.official.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <User class="h-5 w-5 text-blue-600" />
                                    Personal Information
                                </h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-if="props.official.birth_date" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-blue-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Birth Date</Label>
                                            <p class="text-sm text-gray-900">{{ formatDateShort(props.official.birth_date) }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.gender" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <User class="h-4 w-4 text-green-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Gender</Label>
                                            <p class="text-sm text-gray-900 capitalize">{{ props.official.gender }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.civil_status" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <Briefcase class="h-4 w-4 text-purple-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Civil Status</Label>
                                            <p class="text-sm text-gray-900">{{ props.official.civil_status }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.address" class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg sm:col-span-2">
                                        <MapPin class="h-4 w-4 text-orange-600 mt-1" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Address</Label>
                                            <p class="text-sm text-gray-900">{{ props.official.address }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Official Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <Briefcase class="h-5 w-5 text-green-600" />
                                    Official Information
                                </h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                                        <Briefcase class="h-4 w-4 text-blue-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Position</Label>
                                            <p class="text-sm text-gray-900 font-medium">{{ props.official.position }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-purple-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Sort Order</Label>
                                            <p class="text-sm text-gray-900 font-medium">{{ props.official.sort_order }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.term_start" class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-green-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Term Start</Label>
                                            <p class="text-sm text-gray-900">{{ formatDateShort(props.official.term_start) }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.term_end" class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-orange-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Term End</Label>
                                            <p class="text-sm text-gray-900">{{ formatDateShort(props.official.term_end) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div v-if="props.official.email || props.official.phone" class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <Phone class="h-5 w-5 text-blue-600" />
                                    Contact Information
                                </h3>

                                <div class="space-y-3">
                                    <div v-if="props.official.email" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                                        <Mail class="h-4 w-4 text-blue-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Email</Label>
                                            <p class="text-sm text-gray-900">{{ props.official.email }}</p>
                                        </div>
                                    </div>

                                    <div v-if="props.official.phone" class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                                        <Phone class="h-4 w-4 text-green-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Phone</Label>
                                            <p class="text-sm text-gray-900">{{ props.official.phone }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Biography -->
                            <div v-if="props.official.biography" class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <FileText class="h-5 w-5 text-purple-600" />
                                    Biography
                                </h3>

                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ props.official.biography }}</p>
                                </div>
                            </div>

                            <!-- System Information -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900">System Information</h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-gray-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Created</Label>
                                            <p class="text-sm text-gray-900">{{ formatDateTime(props.official.created_at) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <Calendar class="h-4 w-4 text-gray-600" />
                                        <div>
                                            <Label class="text-xs font-medium text-gray-500">Last Updated</Label>
                                            <p class="text-sm text-gray-900">{{ formatDateTime(props.official.updated_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200">
                                <Button variant="outline" @click="window.history.back()" class="w-full sm:w-auto">
                                    Back
                                </Button>
                                <Link :href="`/staff/barangay-officials/${props.official.id}/edit`">
                                    <Button class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-medium h-10 px-6">
                                        <Edit class="h-4 w-4 mr-2" />
                                        Edit Official
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </StaffLayout>
</template>
