<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Search, Eye, Edit, Trash2, Users, Calendar, Phone, Mail, Upload, X, User, FileText, MapPin } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useUtils } from '@/composables/useUtils';

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
    term_start?: number;
    term_end?: number;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    officials: BarangayOfficial[];
}

const props = defineProps<Props>();

// Composables
const { staffBarangayOfficialsBreadcrumbs } = useBreadcrumbs();
const { formatDateShort } = useUtils();

// Breadcrumbs
const breadcrumbs = staffBarangayOfficialsBreadcrumbs.value;

// Search functionality
const searchQuery = ref('');
const filteredOfficials = computed(() => {
    if (!searchQuery.value) return props.officials;

    const query = searchQuery.value.toLowerCase();
    return props.officials.filter(official =>
        official.first_name.toLowerCase().includes(query) ||
        official.last_name.toLowerCase().includes(query) ||
        official.position.toLowerCase().includes(query) ||
        (official.email && official.email.toLowerCase().includes(query))
    );
});

// Sheet states
const createSheetOpen = ref(false);
const editSheetOpen = ref(false);
const viewSheetOpen = ref(false);
const selectedOfficial = ref<BarangayOfficial | null>(null);

// Dialog states
const deleteDialogOpen = ref(false);
const officialToDelete = ref<BarangayOfficial | null>(null);

// Edit form state
const editForm = ref<any>(null);
const editPhotoPreview = ref<string | null>(null);

// Form for creating officials
const createForm = useForm({
    first_name: '',
    last_name: '',
    middle_name: '',
    suffix: '',
    position: '',
    email: '',
    phone: '',
    address: '',
    birth_date: '',
    gender: '',
    civil_status: '',
    photo: null as File | null,
    biography: '',
    term_start: '',
    term_end: '',
    is_active: true,
});

// Photo preview
const photoPreview = ref<string | null>(null);

// Sheet functions
const openCreateSheet = () => {
    createSheetOpen.value = true;
    resetCreateForm();
};

const openEditSheet = (official: BarangayOfficial) => {
    selectedOfficial.value = official;

    // Initialize edit form with official data
    editForm.value = useForm({
        first_name: official.first_name,
        last_name: official.last_name,
        middle_name: official.middle_name || '',
        suffix: official.suffix || '',
        position: official.position,
        email: official.email || '',
        phone: official.phone || '',
        address: official.address || '',
        birth_date: official.birth_date ? official.birth_date.split('T')[0] : '',
        gender: official.gender || '',
        civil_status: official.civil_status || '',
        photo: null as File | null,
        biography: official.biography || '',
        term_start: official.term_start || '',
        term_end: official.term_end || '',
        is_active: official.is_active,
    });

    // Set photo preview
    editPhotoPreview.value = official.photo ? `/storage/${official.photo}` : null;

    editSheetOpen.value = true;
};

const openViewSheet = (official: BarangayOfficial) => {
    selectedOfficial.value = official;
    viewSheetOpen.value = true;
};

const resetCreateForm = () => {
    createForm.reset();
    photoPreview.value = null;
};

const handlePhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        createForm.photo = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    createForm.photo = null;
    photoPreview.value = null;
};

const triggerPhotoUpload = () => {
    const fileInput = document.getElementById('photo-upload') as HTMLInputElement;
    if (fileInput) {
        fileInput.click();
    }
};

const triggerEditPhotoUpload = () => {
    const fileInput = document.getElementById('edit-photo-upload') as HTMLInputElement;
    if (fileInput) {
        fileInput.click();
    }
};

const handleEditPhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        editForm.value.photo = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            editPhotoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeEditPhoto = () => {
    editForm.value.photo = null;
    editPhotoPreview.value = selectedOfficial.value?.photo ? `/storage/${selectedOfficial.value.photo}` : null;
};

const submitEdit = () => {
    if (editForm.value && selectedOfficial.value) {
        editForm.value.put(`/staff/barangay-officials/${selectedOfficial.value.id}`, {
            forceFormData: true,
            onSuccess: () => {
                toast.success(`${editForm.value.first_name} ${editForm.value.last_name}'s information has been updated.`);
                editSheetOpen.value = false;
                editForm.value = null;
                editPhotoPreview.value = null;
            },
            onError: (errors: any) => {
                toast.error('Failed to update barangay official. Please check the form for errors and try again.');
            }
        });
    }
};

const submitCreate = () => {
    createForm.post('/staff/barangay-officials', {
        forceFormData: true,
        onSuccess: () => {
            toast.success(`${createForm.first_name} ${createForm.last_name} has been added to the officials directory.`);
            createSheetOpen.value = false;
            resetCreateForm();
        },
        onError: (errors) => {
            toast.error('Failed to create barangay official. Please check the form for errors and try again.');
        }
    });
};

// Delete confirmation
const openDeleteDialog = (official: BarangayOfficial) => {
    officialToDelete.value = official;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (officialToDelete.value) {
        const deleteForm = useForm({});
        deleteForm.delete(`/staff/barangay-officials/${officialToDelete.value.id}`, {
            onSuccess: () => {
                toast.success(`${officialToDelete.value?.first_name} ${officialToDelete.value?.last_name} has been removed from the officials directory.`);
                deleteDialogOpen.value = false;
                officialToDelete.value = null;
            },
            onError: () => {
                toast.error('Failed to delete barangay official. An error occurred while deleting the official. Please try again.');
            }
        });
    }
};

const cancelDelete = () => {
    deleteDialogOpen.value = false;
    officialToDelete.value = null;
};

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

// Position options
const positionOptions = [
    'Captain',
    'Secretary',
    'Treasurer',
    'Councilor',
    'SK Chairman',
    'SK Councilor',
    'Barangay Health Worker',
    'Barangay Nutrition Scholar',
    'Other'
];

// Gender options
const genderOptions = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' }
];

// Civil status options
const civilStatusOptions = [
    'Single',
    'Married',
    'Widowed',
    'Divorced',
    'Separated'
];
</script>

<template>
    <Head title="Barangay Officials" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-full">
                <!-- Header Section -->
                <div class="mb-6">
                    <div class="space-y-4">
                        <!-- Page Title -->
                        <div class="text-center sm:text-left">
                            <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                                Barangay Officials
                            </h1>
                            <p class="text-base md:text-lg text-gray-600 font-medium mt-1">Manage your barangay officials and their information</p>
                        </div>

                        <!-- Quick Stats Overview -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200 text-center">
                                <Users class="h-6 w-6 text-blue-600 mx-auto mb-2" />
                                <p class="text-2xl font-bold text-gray-900">{{ props.officials.length }}</p>
                                <p class="text-sm text-gray-600">Total Officials</p>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200 text-center">
                                <Calendar class="h-6 w-6 text-green-600 mx-auto mb-2" />
                                <p class="text-2xl font-bold text-gray-900">{{ props.officials.filter(o => o.is_active).length }}</p>
                                <p class="text-sm text-gray-600">Active</p>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200 text-center">
                                <Users class="h-6 w-6 text-purple-600 mx-auto mb-2" />
                                <p class="text-2xl font-bold text-gray-900">{{ props.officials.filter(o => o.position === 'Captain').length }}</p>
                                <p class="text-sm text-gray-600">Captains</p>
                            </div>
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200 text-center">
                                <Users class="h-6 w-6 text-orange-600 mx-auto mb-2" />
                                <p class="text-2xl font-bold text-gray-900">{{ props.officials.filter(o => o.position === 'Councilor').length }}</p>
                                <p class="text-sm text-gray-600">Councilors</p>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                            <!-- Search -->
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search officials..."
                                        class="pl-10 h-10 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Add Button -->
                            <Button @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white font-medium h-10 px-6 shadow-lg">
                                <Plus class="h-4 w-4 mr-2" />
                                Add New Official
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Officials List -->
                <div v-if="filteredOfficials.length > 0" class="space-y-4">
                    <!-- List Header -->
                    <div class="flex items-center justify-between py-2 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Officials Directory
                            <span class="text-sm font-normal text-gray-500 ml-2">({{ filteredOfficials.length }} {{ filteredOfficials.length === 1 ? 'official' : 'officials' }})</span>
                        </h2>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span>Sort by:</span>
                            <Badge variant="outline" class="text-xs">Position</Badge>
                        </div>
                    </div>

                    <!-- Officials Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <Card v-for="official in filteredOfficials" :key="official.id" class="group shadow-sm border-gray-200 hover:shadow-lg hover:border-blue-300 transition-all duration-200 cursor-pointer" @click="openViewSheet(official)">
                            <CardContent class="p-4">
                                <!-- Official Header -->
                                <div class="flex items-start gap-4 mb-4">
                                    <!-- Photo -->
                                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 ring-2 ring-gray-100 group-hover:ring-blue-200 transition-all">
                                        <img
                                            v-if="official.photo"
                                            :src="`/storage/${official.photo}`"
                                            :alt="`${official.first_name} ${official.last_name}`"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center bg-gray-300">
                                            <Users class="h-8 w-8 text-gray-500" />
                                        </div>
                                    </div>

                                    <!-- Name and Position -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                                            {{ official.first_name }} {{ official.last_name }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Badge :class="getPositionColor(official.position)" class="text-xs font-medium">
                                                {{ official.position }}
                                            </Badge>
                                            <Badge :class="getStatusColor(official.is_active)" class="text-xs">
                                                {{ official.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Info -->
                                <div v-if="official.email || official.phone" class="space-y-2 mb-4">
                                    <div v-if="official.email" class="flex items-center gap-2 text-sm text-gray-600">
                                        <Mail class="h-4 w-4 text-blue-600" />
                                        <span class="truncate">{{ official.email }}</span>
                                    </div>
                                    <div v-if="official.phone" class="flex items-center gap-2 text-sm text-gray-600">
                                        <Phone class="h-4 w-4 text-green-600" />
                                        <span>{{ official.phone }}</span>
                                    </div>
                                </div>

                                <!-- Term Info -->
                                <div v-if="official.term_start || official.term_end" class="text-xs text-gray-500 mb-4 p-2 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        <span class="font-medium">Term:</span> {{ official.term_start || 'N/A' }}
                                        <span v-if="official.term_start && official.term_end"> - </span>
                                        {{ official.term_end || 'Present' }}
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="flex gap-2 pt-2 border-t border-gray-100">
                                    <Button @click.stop="openViewSheet(official)" variant="outline" size="sm" class="flex-1 h-8 text-blue-600 border-blue-200 hover:bg-blue-50">
                                        <Eye class="h-3 w-3 mr-1" />
                                        View
                                    </Button>
                                    <Button @click.stop="openEditSheet(official)" variant="outline" size="sm" class="flex-1 h-8 text-green-600 border-green-200 hover:bg-green-50">
                                        <Edit class="h-3 w-3 mr-1" />
                                        Edit
                                    </Button>
                                    <Button
                                        @click.stop="openDeleteDialog(official)"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 px-3 text-red-600 border-red-200 hover:bg-red-50"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <Users class="h-10 w-10 text-blue-600" />
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 mb-3">
                            {{ searchQuery ? 'No officials found' : 'No officials yet' }}
                        </h3>

                        <p class="text-gray-600 mb-8 leading-relaxed">
                            {{ searchQuery
                                ? 'Try adjusting your search terms or clear the search to see all officials.'
                                : 'Start building your barangay officials directory by adding your first official.'
                            }}
                        </p>

                        <div class="space-y-4">
                            <Button v-if="!searchQuery" @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 shadow-lg">
                                <Plus class="h-5 w-5 mr-2" />
                                Add Your First Official
                            </Button>

                            <div v-if="searchQuery" class="flex gap-3 justify-center">
                                <Button @click="searchQuery = ''" variant="outline" class="px-6 py-2">
                                    Clear Search
                                </Button>
                                <Button @click="openCreateSheet" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Official
                                </Button>
                            </div>
                        </div>

                        <!-- Helpful Tips -->
                        <div v-if="!searchQuery" class="mt-8 p-4 bg-blue-50 rounded-lg text-left">
                            <h4 class="text-sm font-semibold text-blue-900 mb-2">💡 Getting Started Tips:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Start with the Barangay Captain</li>
                                <li>• Add councilors and key officials</li>
                                <li>• Include contact information for easy access</li>
                                <li>• Upload photos for better identification</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Official Sheet -->
        <Sheet :open="createSheetOpen" @update:open="createSheetOpen = $event">
            <SheetContent class="p-0 flex flex-col h-full overflow-y-auto">
                <SheetHeader class="p-6 pb-4 border-b border-gray-200">
                    <SheetTitle class="flex items-center gap-2 text-xl font-semibold">
                        <Plus class="h-5 w-5 text-blue-600" />
                        Create Barangay Official
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Add a new barangay official to the system
                    </SheetDescription>
                </SheetHeader>

                <form @submit.prevent="submitCreate" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Header Section -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <User class="h-5 w-5 text-blue-600" />
                            Basic Information
                        </h3>

                        <!-- Photo Upload -->
                        <div class="mb-6">
                            <Label class="text-sm font-medium text-gray-700 mb-3 block">Official Photo</Label>
                            <div class="flex items-center gap-6">
                                <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 ring-2 ring-gray-100">
                                    <img
                                        v-if="photoPreview"
                                        :src="photoPreview"
                                        alt="Photo preview"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-300">
                                        <User class="h-8 w-8 text-gray-500" />
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <input
                                        type="file"
                                        @change="handlePhotoChange"
                                        accept="image/*"
                                        class="hidden"
                                        id="photo-upload"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="triggerPhotoUpload"
                                        size="sm"
                                        class="bg-white hover:bg-gray-50"
                                    >
                                        <Upload class="h-4 w-4 mr-2" />
                                        Upload Photo
                                    </Button>
                                    <Button
                                        v-if="photoPreview"
                                        type="button"
                                        variant="outline"
                                        @click="removePhoto"
                                        size="sm"
                                        class="text-red-600 border-red-200 hover:bg-red-50"
                                    >
                                        <X class="h-4 w-4 mr-2" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Name Fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="first_name" class="text-sm font-medium text-gray-700">First Name *</Label>
                                <Input
                                    id="first_name"
                                    v-model="createForm.first_name"
                                    placeholder="Enter first name"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': createForm.errors.first_name }"
                                />
                                <p v-if="createForm.errors.first_name" class="text-red-500 text-xs mt-1">{{ createForm.errors.first_name }}</p>
                            </div>
                            <div>
                                <Label for="last_name" class="text-sm font-medium text-gray-700">Last Name *</Label>
                                <Input
                                    id="last_name"
                                    v-model="createForm.last_name"
                                    placeholder="Enter last name"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': createForm.errors.last_name }"
                                />
                                <p v-if="createForm.errors.last_name" class="text-red-500 text-xs mt-1">{{ createForm.errors.last_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="middle_name" class="text-sm font-medium text-gray-700">Middle Name</Label>
                                <Input
                                    id="middle_name"
                                    v-model="createForm.middle_name"
                                    placeholder="Enter middle name"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <Label for="suffix" class="text-sm font-medium text-gray-700">Suffix</Label>
                                <Input
                                    id="suffix"
                                    v-model="createForm.suffix"
                                    placeholder="Jr., Sr., III, etc."
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Official Position Section -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Users class="h-5 w-5 text-green-600" />
                            Official Position
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="position" class="text-sm font-medium text-gray-700">Position *</Label>
                                <Select v-model="createForm.position">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500">
                                        <SelectValue placeholder="Select position" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="position in positionOptions" :key="position" :value="position">
                                            {{ position }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="createForm.errors.position" class="text-red-500 text-xs mt-1">{{ createForm.errors.position }}</p>
                            </div>
                            <div>
                                <Label for="term_start" class="text-sm font-medium text-gray-700">Term Start Year</Label>
                                <Input
                                    id="term_start"
                                    v-model="createForm.term_start"
                                    type="number"
                                    min="2000"
                                    max="2050"
                                    placeholder="2024"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="term_end" class="text-sm font-medium text-gray-700">Term End Year</Label>
                                <Input
                                    id="term_end"
                                    v-model="createForm.term_end"
                                    type="number"
                                    min="2000"
                                    max="2050"
                                    placeholder="2027"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>
                            <div class="flex items-center space-x-2 pt-6">
                                <Switch id="is_active" v-model:checked="createForm.is_active" />
                                <Label for="is_active" class="text-sm font-medium text-gray-700">Active Official</Label>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details Section -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <User class="h-5 w-5 text-purple-600" />
                            Personal Details
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <div>
                                <Label for="birth_date" class="text-sm font-medium text-gray-700">Birth Date</Label>
                                <Input
                                    id="birth_date"
                                    v-model="createForm.birth_date"
                                    type="date"
                                    class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                                />
                            </div>
                            <div>
                                <Label for="gender" class="text-sm font-medium text-gray-700">Gender</Label>
                                <Select v-model="createForm.gender">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in genderOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label for="civil_status" class="text-sm font-medium text-gray-700">Civil Status</Label>
                                <Select v-model="createForm.civil_status">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500">
                                        <SelectValue placeholder="Select civil status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in civilStatusOptions" :key="status" :value="status">
                                            {{ status }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <Label for="address" class="text-sm font-medium text-gray-700">Address</Label>
                            <Textarea
                                id="address"
                                v-model="createForm.address"
                                placeholder="Enter complete address"
                                rows="3"
                                class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                            />
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Phone class="h-5 w-5 text-orange-600" />
                            Contact Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="email" class="text-sm font-medium text-gray-700">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    class="mt-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                    :class="{ 'border-red-500': createForm.errors.email }"
                                />
                                <p v-if="createForm.errors.email" class="text-red-500 text-xs mt-1">{{ createForm.errors.email }}</p>
                            </div>
                            <div>
                                <Label for="phone" class="text-sm font-medium text-gray-700">Phone Number</Label>
                                <Input
                                    id="phone"
                                    v-model="createForm.phone"
                                    placeholder="Enter phone number"
                                    class="mt-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Biography Section -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-gray-600" />
                            Biography & Additional Information
                        </h3>

                        <div>
                            <Label for="biography" class="text-sm font-medium text-gray-700">Biography</Label>
                            <Textarea
                                id="biography"
                                v-model="createForm.biography"
                                placeholder="Enter biography, achievements, or additional information about this official..."
                                rows="4"
                                class="mt-1 border-gray-200 focus:border-gray-500 focus:ring-gray-500"
                            />
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
                            {{ createForm.processing ? 'Creating...' : 'Create Official' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Edit Official Sheet -->
        <Sheet :open="editSheetOpen" @update:open="editSheetOpen = $event">
            <SheetContent class="p-0 flex flex-col h-full overflow-y-auto">
                <SheetHeader class="p-6 pb-4 border-b border-gray-200">
                    <SheetTitle class="flex items-center gap-2 text-xl font-semibold">
                        <Edit class="h-5 w-5 text-green-600" />
                        Edit {{ selectedOfficial?.first_name }} {{ selectedOfficial?.last_name }}
                    </SheetTitle>
                    <SheetDescription class="text-gray-600">
                        Update the official's information
                    </SheetDescription>
                </SheetHeader>

                <form v-if="editForm" @submit.prevent="submitEdit" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Header Section -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <User class="h-5 w-5 text-green-600" />
                            Basic Information
                        </h3>

                        <!-- Photo Upload -->
                        <div class="mb-6">
                            <Label class="text-sm font-medium text-gray-700 mb-3 block">Official Photo</Label>
                            <div class="flex items-center gap-6">
                                <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 ring-2 ring-gray-100">
                                    <img
                                        v-if="editPhotoPreview"
                                        :src="editPhotoPreview"
                                        alt="Photo preview"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-300">
                                        <User class="h-8 w-8 text-gray-500" />
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <input
                                        type="file"
                                        @change="handleEditPhotoChange"
                                        accept="image/*"
                                        class="hidden"
                                        id="edit-photo-upload"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="triggerEditPhotoUpload"
                                        size="sm"
                                        class="bg-white hover:bg-gray-50"
                                    >
                                        <Upload class="h-4 w-4 mr-2" />
                                        Upload Photo
                                    </Button>
                                    <Button
                                        v-if="editPhotoPreview"
                                        type="button"
                                        variant="outline"
                                        @click="removeEditPhoto"
                                        size="sm"
                                        class="text-red-600 border-red-200 hover:bg-red-50"
                                    >
                                        <X class="h-4 w-4 mr-2" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Name Fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="edit_first_name" class="text-sm font-medium text-gray-700">First Name *</Label>
                                <Input
                                    id="edit_first_name"
                                    v-model="editForm.first_name"
                                    placeholder="Enter first name"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                    :class="{ 'border-red-500': editForm.errors.first_name }"
                                />
                                <p v-if="editForm.errors.first_name" class="text-red-500 text-xs mt-1">{{ editForm.errors.first_name }}</p>
                            </div>
                            <div>
                                <Label for="edit_last_name" class="text-sm font-medium text-gray-700">Last Name *</Label>
                                <Input
                                    id="edit_last_name"
                                    v-model="editForm.last_name"
                                    placeholder="Enter last name"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                    :class="{ 'border-red-500': editForm.errors.last_name }"
                                />
                                <p v-if="editForm.errors.last_name" class="text-red-500 text-xs mt-1">{{ editForm.errors.last_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="edit_middle_name" class="text-sm font-medium text-gray-700">Middle Name</Label>
                                <Input
                                    id="edit_middle_name"
                                    v-model="editForm.middle_name"
                                    placeholder="Enter middle name"
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>
                            <div>
                                <Label for="edit_suffix" class="text-sm font-medium text-gray-700">Suffix</Label>
                                <Input
                                    id="edit_suffix"
                                    v-model="editForm.suffix"
                                    placeholder="Jr., Sr., III, etc."
                                    class="mt-1 border-gray-200 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Official Position Section -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Users class="h-5 w-5 text-blue-600" />
                            Official Position
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="edit_position" class="text-sm font-medium text-gray-700">Position *</Label>
                                <Select v-model="editForm.position">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <SelectValue placeholder="Select position" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="position in positionOptions" :key="position" :value="position">
                                            {{ position }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="editForm.errors.position" class="text-red-500 text-xs mt-1">{{ editForm.errors.position }}</p>
                            </div>
                            <div>
                                <Label for="edit_term_start" class="text-sm font-medium text-gray-700">Term Start Year</Label>
                                <Input
                                    id="edit_term_start"
                                    v-model="editForm.term_start"
                                    type="number"
                                    min="2000"
                                    max="2050"
                                    placeholder="2024"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="edit_term_end" class="text-sm font-medium text-gray-700">Term End Year</Label>
                                <Input
                                    id="edit_term_end"
                                    v-model="editForm.term_end"
                                    type="number"
                                    min="2000"
                                    max="2050"
                                    placeholder="2027"
                                    class="mt-1 border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div class="flex items-center space-x-2 pt-6">
                                <Switch id="edit_is_active" v-model:checked="editForm.is_active" />
                                <Label for="edit_is_active" class="text-sm font-medium text-gray-700">Active Official</Label>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details Section -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <User class="h-5 w-5 text-purple-600" />
                            Personal Details
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <div>
                                <Label for="edit_birth_date" class="text-sm font-medium text-gray-700">Birth Date</Label>
                                <Input
                                    id="edit_birth_date"
                                    v-model="editForm.birth_date"
                                    type="date"
                                    class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                                />
                            </div>
                            <div>
                                <Label for="edit_gender" class="text-sm font-medium text-gray-700">Gender</Label>
                                <Select v-model="editForm.gender">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in genderOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label for="edit_civil_status" class="text-sm font-medium text-gray-700">Civil Status</Label>
                                <Select v-model="editForm.civil_status">
                                    <SelectTrigger class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500">
                                        <SelectValue placeholder="Select civil status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in civilStatusOptions" :key="status" :value="status">
                                            {{ status }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <Label for="edit_address" class="text-sm font-medium text-gray-700">Address</Label>
                            <Textarea
                                id="edit_address"
                                v-model="editForm.address"
                                placeholder="Enter complete address"
                                rows="3"
                                class="mt-1 border-gray-200 focus:border-purple-500 focus:ring-purple-500"
                            />
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <Phone class="h-5 w-5 text-orange-600" />
                            Contact Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="edit_email" class="text-sm font-medium text-gray-700">Email Address</Label>
                                <Input
                                    id="edit_email"
                                    v-model="editForm.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    class="mt-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                    :class="{ 'border-red-500': editForm.errors.email }"
                                />
                                <p v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</p>
                            </div>
                            <div>
                                <Label for="edit_phone" class="text-sm font-medium text-gray-700">Phone Number</Label>
                                <Input
                                    id="edit_phone"
                                    v-model="editForm.phone"
                                    placeholder="Enter phone number"
                                    class="mt-1 border-gray-200 focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Biography Section -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-gray-600" />
                            Biography & Additional Information
                        </h3>

                        <div>
                            <Label for="edit_biography" class="text-sm font-medium text-gray-700">Biography</Label>
                            <Textarea
                                id="edit_biography"
                                v-model="editForm.biography"
                                placeholder="Enter biography, achievements, or additional information about this official..."
                                rows="4"
                                class="mt-1 border-gray-200 focus:border-gray-500 focus:ring-gray-500"
                            />
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
                            {{ editForm.processing ? 'Updating...' : 'Update Official' }}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- View Official Sheet -->
        <Sheet :open="viewSheetOpen" @update:open="viewSheetOpen = $event">
            <SheetContent class="p-0 flex flex-col h-full overflow-y-auto">
                <!-- Enhanced Header with Gradient -->
                <SheetHeader class="relative p-6 pb-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="relative">
                        <SheetTitle class="flex items-center gap-3 text-2xl font-bold">
                            <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                <Eye class="h-6 w-6" />
                            </div>
                            {{ selectedOfficial?.first_name }} {{ selectedOfficial?.last_name }}
                        </SheetTitle>
                        <SheetDescription class="text-blue-100 mt-2 text-lg">
                            Official Profile & Information
                        </SheetDescription>
                    </div>
                </SheetHeader>

                <div v-if="selectedOfficial" class="flex-1 overflow-y-auto">
                    <!-- Hero Section with Photo and Basic Info -->
                    <div class="relative bg-gradient-to-br from-blue-50 via-purple-50 to-indigo-50 p-8">
                        <div class="flex flex-col lg:flex-row items-center lg:items-start gap-8">
                            <!-- Enhanced Photo Section -->
                            <div class="flex-shrink-0">
                                <div class="relative">
                                    <div class="w-32 h-32 rounded-2xl overflow-hidden bg-white shadow-xl ring-4 ring-white/50">
                                        <img
                                            v-if="selectedOfficial.photo"
                                            :src="`/storage/${selectedOfficial.photo}`"
                                            :alt="`${selectedOfficial.first_name} ${selectedOfficial.last_name}`"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                                            <User class="h-16 w-16 text-gray-500" />
                                        </div>
                                    </div>
                                    <!-- Status Indicator -->
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full border-4 border-white flex items-center justify-center"
                                         :class="selectedOfficial.is_active ? 'bg-green-500' : 'bg-gray-400'">
                                        <div class="w-3 h-3 rounded-full bg-white"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="flex-1 text-center lg:text-left">
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                    {{ selectedOfficial.first_name }} {{ selectedOfficial.last_name }}
                                    <span v-if="selectedOfficial.middle_name" class="text-gray-600">{{ selectedOfficial.middle_name }}</span>
                                    <span v-if="selectedOfficial.suffix" class="text-gray-600">{{ selectedOfficial.suffix }}</span>
                                </h1>

                                <div class="flex flex-wrap justify-center lg:justify-start gap-3 mb-4">
                                    <Badge :class="getPositionColor(selectedOfficial.position)" class="text-base px-4 py-2 font-semibold">
                                        {{ selectedOfficial.position }}
                                    </Badge>
                                    <Badge :class="getStatusColor(selectedOfficial.is_active)" class="text-base px-4 py-2 font-semibold">
                                        {{ selectedOfficial.is_active ? 'Active Official' : 'Inactive Official' }}
                                    </Badge>
                                </div>

                                <!-- Term Information -->
                                <div v-if="selectedOfficial.term_start || selectedOfficial.term_end" class="flex flex-wrap justify-center lg:justify-start gap-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        <span>
                                            Term: {{ selectedOfficial.term_start || 'N/A' }}
                                            <span v-if="selectedOfficial.term_start && selectedOfficial.term_end"> - </span>
                                            {{ selectedOfficial.term_end || 'Present' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Cards -->
                    <div class="p-6 space-y-6">
                        <!-- Personal Information Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <User class="h-5 w-5" />
                                    </div>
                                    Personal Information
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div v-if="selectedOfficial.birth_date" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Birth Date</Label>
                                        <p class="text-lg text-gray-900 font-medium">{{ formatDateShort(selectedOfficial.birth_date) }}</p>
                                    </div>
                                    <div v-if="selectedOfficial.gender" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Gender</Label>
                                        <p class="text-lg text-gray-900 font-medium capitalize">{{ selectedOfficial.gender }}</p>
                                    </div>
                                    <div v-if="selectedOfficial.civil_status" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Civil Status</Label>
                                        <p class="text-lg text-gray-900 font-medium">{{ selectedOfficial.civil_status }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Official Information Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <Users class="h-5 w-5" />
                                    </div>
                                    Official Information
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Status</Label>
                                        <Badge :class="getStatusColor(selectedOfficial.is_active)" class="text-base px-3 py-1 font-semibold">
                                            {{ selectedOfficial.is_active ? 'Active Official' : 'Inactive Official' }}
                                        </Badge>
                                    </div>
                                    <div v-if="selectedOfficial.term_start || selectedOfficial.term_end" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Term Period</Label>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-gray-500" />
                                            <span class="text-lg text-gray-900 font-medium">
                                                {{ selectedOfficial.term_start || 'N/A' }}
                                                <span v-if="selectedOfficial.term_start && selectedOfficial.term_end"> - </span>
                                                {{ selectedOfficial.term_end || 'Present' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Card -->
                        <div v-if="selectedOfficial.email || selectedOfficial.phone" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-teal-500 p-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <Phone class="h-5 w-5" />
                                    </div>
                                    Contact Information
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div v-if="selectedOfficial.email" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Email Address</Label>
                                        <a :href="`mailto:${selectedOfficial.email}`" class="text-lg text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                            {{ selectedOfficial.email }}
                                        </a>
                                    </div>
                                    <div v-if="selectedOfficial.phone" class="space-y-2">
                                        <Label class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Phone Number</Label>
                                        <a :href="`tel:${selectedOfficial.phone}`" class="text-lg text-green-600 hover:text-green-800 font-medium transition-colors">
                                            {{ selectedOfficial.phone }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Card -->
                        <div v-if="selectedOfficial.address" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <MapPin class="h-5 w-5" />
                                    </div>
                                    Address
                                </h3>
                            </div>
                            <div class="p-6">
                                <p class="text-lg text-gray-900 leading-relaxed">{{ selectedOfficial.address }}</p>
                            </div>
                        </div>

                        <!-- Biography Card -->
                        <div v-if="selectedOfficial.biography" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-600 to-slate-600 p-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <FileText class="h-5 w-5" />
                                    </div>
                                    Biography
                                </h3>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-900 leading-relaxed whitespace-pre-wrap">{{ selectedOfficial.biography }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-red-600" />
                        Delete Official
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ officialToDelete?.first_name }} {{ officialToDelete?.last_name }}</strong>?
                        This action cannot be undone.
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

