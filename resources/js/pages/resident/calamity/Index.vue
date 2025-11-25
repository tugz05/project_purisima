<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { AlertTriangle, Clock, CheckCircle, AlertCircle, Eye, Navigation, Loader2, MapPin, Users, Heart, Baby, Accessibility, Activity } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useUtils } from '@/composables/useUtils';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';

interface CalamityReport {
    id: number;
    calamity_type: string;
    severity: string;
    status: string;
    description: string;
    needs: string[];
    number_of_people: number;
    created_at: string;
    acknowledged_at?: string;
    assisted_at?: string;
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
        status?: string;
    };
}

const props = defineProps<Props>();

const { formatDateShort } = useUtils();
const filterForm = useForm({
    status: props.filters.status || 'all',
});

const modalOpen = ref(false);
const locationLoading = ref(false);
const locationError = ref<string | null>(null);

const form = useForm({
    latitude: null as number | null,
    longitude: null as number | null,
    address: '',
    location_notes: '',
    calamity_type: 'other',
    severity: 'medium',
    description: '',
    needs: [] as string[],
    specific_needs: '',
    number_of_people: 1,
    has_elderly: false,
    has_children: false,
    has_pwd: false,
    has_pregnant: false,
    medical_conditions: '',
    location_shared: true,
});

const availableNeeds = [
    { value: 'food', label: 'Food' },
    { value: 'water', label: 'Water' },
    { value: 'medicine', label: 'Medicine' },
    { value: 'shelter', label: 'Shelter' },
    { value: 'clothing', label: 'Clothing' },
    { value: 'evacuation', label: 'Evacuation' },
    { value: 'communication', label: 'Communication' },
    { value: 'transportation', label: 'Transportation' },
    { value: 'other', label: 'Other' },
];

const getCurrentLocation = () => {
    locationLoading.value = true;
    locationError.value = null;

    if (!navigator.geolocation) {
        locationError.value = 'Geolocation is not supported by your browser.';
        locationLoading.value = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
            locationLoading.value = false;
            form.address = `${form.latitude.toFixed(6)}, ${form.longitude.toFixed(6)}`;
            toast.success('Location captured successfully!');
        },
        (error) => {
            locationLoading.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    locationError.value = 'Location access denied. Please enable location permissions.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    locationError.value = 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    locationError.value = 'Location request timed out.';
                    break;
                default:
                    locationError.value = 'An unknown error occurred while getting location.';
                    break;
            }
            toast.error(locationError.value);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }
    );
};

const openModal = () => {
    modalOpen.value = true;
    // Auto-get location when modal opens
    getCurrentLocation();
};

const closeModal = () => {
    modalOpen.value = false;
    form.reset();
    locationError.value = null;
};

const toggleNeed = (need: string) => {
    const index = form.needs.indexOf(need);
    if (index > -1) {
        form.needs.splice(index, 1);
    } else {
        form.needs.push(need);
    }
};

const submitReport = () => {
    if (!form.latitude || !form.longitude) {
        toast.error('Please share your location before submitting the report.');
        return;
    }

    form.post('/resident/calamity', {
        onSuccess: () => {
            toast.success('Emergency report submitted! Help is on the way.');
            closeModal();
        },
        onError: (errors) => {
            console.error('Form submission error:', errors);
            toast.error('Failed to submit report. Please try again.');
        },
    });
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

const applyFilters = () => {
    filterForm.get('/resident/calamity');
};
</script>

<template>
    <Head title="Emergency Assistance" />

    <ResidentLayout>
        <div class="w-full min-h-full flex flex-col">
            <!-- Main Action Button Section with Animated Background -->
            <div class="relative flex-1 flex items-center justify-center px-4 py-8 md:py-12 lg:py-16 min-h-[60vh] overflow-hidden">
                <!-- Animated Wavy Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900">
                    <!-- Animated Ripple Circles -->
                    <div class="absolute inset-0 overflow-hidden">
                        <div 
                            v-for="i in 8" 
                            :key="i"
                            class="ripple-circle absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-cyan-400/30"
                            :style="{
                                width: `${150 + i * 100}px`,
                                height: `${150 + i * 100}px`,
                                animationDelay: `${i * 0.4}s`,
                            }"
                        ></div>
                    </div>
                    <!-- Additional smaller ripples for depth -->
                    <div class="absolute inset-0 overflow-hidden">
                        <div 
                            v-for="i in 6" 
                            :key="`small-${i}`"
                            class="ripple-circle-small absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border border-cyan-300/20"
                            :style="{
                                width: `${100 + i * 80}px`,
                                height: `${100 + i * 80}px`,
                                animationDelay: `${i * 0.5}s`,
                            }"
                        ></div>
                    </div>
                </div>
                
                <!-- Button Content -->
                <div class="relative z-10 text-center w-full max-w-md">
                    <button
                        @click="openModal"
                        class="group relative w-56 h-56 sm:w-64 sm:h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 mx-auto rounded-full bg-white shadow-2xl hover:shadow-cyan-500/50 transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-cyan-400/50"
                    >
                        <!-- Inner Glow -->
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 blur-xl group-hover:blur-2xl transition-all"></div>
                        
                        <!-- Pulsing Ring Effect -->
                        <div class="absolute inset-0 rounded-full bg-cyan-400/20 animate-ping"></div>
                        
                        <!-- Play Icon -->
                        <div class="relative flex flex-col items-center justify-center h-full z-10">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 mb-3 sm:mb-4">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-cyan-500">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <span class="text-cyan-600 font-bold text-lg sm:text-xl md:text-2xl lg:text-3xl">Get Help</span>
                        </div>
                    </button>
                    
                    <p class="mt-6 sm:mt-8 text-white/90 text-base sm:text-lg md:text-xl font-medium">
                        Tap to report emergency
                    </p>
                    <p class="mt-2 text-white/70 text-sm sm:text-base md:text-lg">
                        Share your location and needs
                    </p>
                </div>
            </div>

            <!-- Reports List Section -->
            <div class="relative bg-white/95 backdrop-blur-sm rounded-t-3xl -mt-8 sm:-mt-12 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10 min-h-[40vh]">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">Your Reports</h2>
                            <Link href="/resident/calamity/map">
                                <Button variant="outline" size="sm" class="w-full sm:w-auto">
                                    <MapPin class="h-4 w-4 mr-2" />
                                    View Map
                                </Button>
                            </Link>
                        </div>

                        <!-- Reports List -->
                        <div class="space-y-3 sm:space-y-4">
                            <Card
                                v-for="report in props.reports.data"
                                :key="report.id"
                                class="shadow-md border-gray-200 hover:shadow-lg transition-shadow"
                            >
                                <CardContent class="pt-4 sm:pt-6 p-4 sm:p-6">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4 mb-3 sm:mb-4">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-base sm:text-lg text-gray-900 flex items-center gap-2">
                                                <AlertTriangle class="h-4 w-4 sm:h-5 sm:w-5 text-red-600 flex-shrink-0" />
                                                <span class="truncate">{{ formatCalamityType(report.calamity_type) }}</span>
                                            </h3>
                                            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                                                {{ formatDateShort(report.created_at) }}
                                            </p>
                                        </div>
                                        <Badge :class="getStatusColor(report.status)" class="self-start sm:self-auto">
                                            <component :is="getStatusIcon(report.status)" class="h-3 w-3 mr-1" />
                                            <span class="text-xs sm:text-sm">{{ formatStatus(report.status) }}</span>
                                        </Badge>
                                    </div>

                                    <div v-if="report.description" class="mb-3">
                                        <p class="text-xs sm:text-sm text-gray-700 line-clamp-2">{{ report.description }}</p>
                                    </div>

                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <Badge
                                            v-for="need in report.needs"
                                            :key="need"
                                            variant="outline"
                                            class="capitalize text-xs"
                                        >
                                            {{ need }}
                                        </Badge>
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t">
                                        <div class="flex items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-600">
                                            <span class="flex items-center gap-1">
                                                <MapPin class="h-3 w-3 sm:h-4 sm:w-4" />
                                                <span>Severity: {{ report.severity }}</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <Users class="h-3 w-3 sm:h-4 sm:w-4" />
                                                <span>{{ report.number_of_people }} people</span>
                                            </span>
                                        </div>
                                        <Link :href="`/resident/calamity/${report.id}`" class="self-start sm:self-auto">
                                            <Button variant="outline" size="sm" class="w-full sm:w-auto">
                                                <Eye class="h-3 w-3 sm:h-4 sm:w-4 mr-1" />
                                                View
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card v-if="props.reports.data.length === 0" class="shadow-md border-gray-200">
                                <CardContent class="py-8 sm:py-12 text-center">
                                    <AlertTriangle class="h-10 w-10 sm:h-12 sm:w-12 text-gray-400 mx-auto mb-3 sm:mb-4" />
                                    <p class="text-gray-500 font-medium text-sm sm:text-base">No reports yet</p>
                                    <p class="text-xs sm:text-sm text-gray-400 mt-1">Tap the button above to report an emergency</p>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Pagination -->
                        <div v-if="props.reports.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 pt-6 border-t">
                            <div class="text-xs sm:text-sm text-gray-600 text-center sm:text-left">
                                Showing {{ props.reports.from }} to {{ props.reports.to }} of {{ props.reports.total }}
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="props.reports.current_page > 1"
                                    :href="`/resident/calamity?page=${props.reports.current_page - 1}&status=${filterForm.status}`"
                                    preserve-state
                                >
                                    <Button variant="outline" size="sm">Previous</Button>
                                </Link>
                                <Link
                                    v-if="props.reports.current_page < props.reports.last_page"
                                    :href="`/resident/calamity?page=${props.reports.current_page + 1}&status=${filterForm.status}`"
                                    preserve-state
                                >
                                    <Button variant="outline" size="sm">Next</Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Emergency Report Modal -->
            <Dialog :open="modalOpen" @update:open="(value) => modalOpen = value">
                <DialogContent class="max-w-md sm:max-w-lg md:max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="text-xl sm:text-2xl font-bold text-red-600 flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 sm:h-6 sm:w-6" />
                            Emergency Report
                        </DialogTitle>
                        <DialogDescription class="text-sm sm:text-base">
                            Share your location and needs for immediate assistance
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitReport" class="space-y-4 sm:space-y-5 mt-4">
                        <!-- Location Section -->
                        <div class="space-y-3 p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                <Label class="text-xs sm:text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <MapPin class="h-3 w-3 sm:h-4 sm:w-4" />
                                    Your Location
                                </Label>
                                <Button
                                    type="button"
                                    @click="getCurrentLocation"
                                    :disabled="locationLoading"
                                    variant="outline"
                                    size="sm"
                                    class="w-full sm:w-auto"
                                >
                                    <Loader2 v-if="locationLoading" class="h-3 w-3 sm:h-4 sm:w-4 mr-2 animate-spin" />
                                    <Navigation v-else class="h-3 w-3 sm:h-4 sm:w-4 mr-2" />
                                    {{ locationLoading ? 'Getting...' : 'Get Location' }}
                                </Button>
                            </div>
                            
                            <div v-if="locationError" class="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-600">
                                {{ locationError }}
                            </div>
                            
                            <div v-if="form.latitude && form.longitude" class="p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                                ✓ Location captured: {{ form.latitude.toFixed(4) }}, {{ form.longitude.toFixed(4) }}
                            </div>

                            <div class="space-y-2">
                                <Label for="address" class="text-xs sm:text-sm">Address (Optional)</Label>
                                <Input
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Your address"
                                    class="text-sm"
                                />
                                <InputError :message="form.errors.address" />
                            </div>
                        </div>

                        <!-- Calamity Type & Severity -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-2">
                                <Label for="calamity_type" class="text-xs sm:text-sm">Type *</Label>
                                <Select v-model="form.calamity_type">
                                    <SelectTrigger class="text-sm">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="typhoon">Typhoon</SelectItem>
                                        <SelectItem value="flood">Flood</SelectItem>
                                        <SelectItem value="earthquake">Earthquake</SelectItem>
                                        <SelectItem value="fire">Fire</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.calamity_type" />
                            </div>

                            <div class="space-y-2">
                                <Label for="severity" class="text-xs sm:text-sm">Severity *</Label>
                                <Select v-model="form.severity">
                                    <SelectTrigger class="text-sm">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Low</SelectItem>
                                        <SelectItem value="medium">Medium</SelectItem>
                                        <SelectItem value="high">High</SelectItem>
                                        <SelectItem value="critical">Critical</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.severity" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description" class="text-xs sm:text-sm">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe the situation..."
                                rows="3"
                                class="text-sm"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Needs -->
                        <div class="space-y-2">
                            <Label class="text-xs sm:text-sm font-semibold flex items-center gap-2">
                                <Heart class="h-3 w-3 sm:h-4 sm:w-4 text-pink-600" />
                                What do you need?
                            </Label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="need in availableNeeds"
                                    :key="need.value"
                                    @click="toggleNeed(need.value)"
                                    :class="[
                                        'p-2 sm:p-3 border-2 rounded-lg cursor-pointer transition-all text-xs sm:text-sm',
                                        form.needs.includes(need.value)
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'w-3 h-3 sm:w-4 sm:h-4 rounded border-2 flex items-center justify-center flex-shrink-0',
                                                form.needs.includes(need.value)
                                                    ? 'border-blue-500 bg-blue-500'
                                                    : 'border-gray-300'
                                            ]"
                                        >
                                            <svg
                                                v-if="form.needs.includes(need.value)"
                                                class="w-2 h-2 sm:w-2.5 sm:h-2.5 text-white"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <span class="font-medium truncate">{{ need.label }}</span>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.needs" />
                        </div>

                        <!-- Specific Needs -->
                        <div class="space-y-2">
                            <Label for="specific_needs" class="text-xs sm:text-sm">Additional Details</Label>
                            <Textarea
                                id="specific_needs"
                                v-model="form.specific_needs"
                                placeholder="Any other specific needs..."
                                rows="2"
                                class="text-sm"
                            />
                            <InputError :message="form.errors.specific_needs" />
                        </div>

                        <!-- People Info -->
                        <div class="space-y-3 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="space-y-2">
                                <Label for="number_of_people" class="text-xs sm:text-sm font-semibold flex items-center gap-2">
                                    <Users class="h-3 w-3 sm:h-4 sm:w-4" />
                                    Number of People *
                                </Label>
                                <Input
                                    id="number_of_people"
                                    v-model.number="form.number_of_people"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="text-sm"
                                />
                                <InputError :message="form.errors.number_of_people" />
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="has_elderly"
                                        :checked="form.has_elderly"
                                        @update:checked="form.has_elderly = $event"
                                    />
                                    <Label for="has_elderly" class="text-xs cursor-pointer flex items-center gap-1">
                                        <Users class="h-3 w-3" />
                                        Elderly
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="has_children"
                                        :checked="form.has_children"
                                        @update:checked="form.has_children = $event"
                                    />
                                    <Label for="has_children" class="text-xs cursor-pointer flex items-center gap-1">
                                        <Baby class="h-3 w-3" />
                                        Children
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="has_pwd"
                                        :checked="form.has_pwd"
                                        @update:checked="form.has_pwd = $event"
                                    />
                                    <Label for="has_pwd" class="text-xs cursor-pointer flex items-center gap-1">
                                        <Accessibility class="h-3 w-3" />
                                        PWD
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="has_pregnant"
                                        :checked="form.has_pregnant"
                                        @update:checked="form.has_pregnant = $event"
                                    />
                                    <Label for="has_pregnant" class="text-xs cursor-pointer flex items-center gap-1">
                                        <Activity class="h-3 w-3" />
                                        Pregnant
                                    </Label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="medical_conditions" class="text-xs sm:text-sm">Medical Conditions</Label>
                                <Textarea
                                    id="medical_conditions"
                                    v-model="form.medical_conditions"
                                    placeholder="Any medical conditions..."
                                    rows="2"
                                    class="text-sm"
                                />
                                <InputError :message="form.errors.medical_conditions" />
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeModal"
                                class="flex-1 order-2 sm:order-1"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing || !form.latitude || !form.longitude"
                                class="flex-1 bg-red-600 hover:bg-red-700 order-1 sm:order-2"
                            >
                                <AlertTriangle class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </ResidentLayout>
</template>

<style scoped>
@keyframes ripple {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(2.5);
        opacity: 0;
    }
}

@keyframes ripple-small {
    0% {
        transform: translate(-50%, -50%) scale(0.9);
        opacity: 0.8;
    }
    100% {
        transform: translate(-50%, -50%) scale(2);
        opacity: 0;
    }
}

.ripple-circle {
    animation: ripple 4s ease-out infinite;
}

.ripple-circle-small {
    animation: ripple-small 5s ease-out infinite;
}

/* Ensure smooth animations */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Prevent horizontal scroll */
.overflow-x-hidden {
    overflow-x: hidden;
}
</style>
