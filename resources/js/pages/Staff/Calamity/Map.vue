<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertTriangle, MapPin, RefreshCw, ArrowLeft, Users, Heart, Navigation, Loader2 } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { toast } from 'vue-sonner';

interface ActiveReport {
    id: number;
    resident_name: string;
    latitude: number;
    longitude: number;
    address: string;
    calamity_type: string;
    severity: string;
    status: string;
    needs: string[];
    number_of_people: number;
    description: string;
}

interface ResidentLocation {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    purok?: string;
    updated_at?: string;
}

interface StaffLocation {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    updated_at?: string;
}

interface Props {
    activeReports: ActiveReport[];
    residentLocations: ResidentLocation[];
    staffLocations: StaffLocation[];
    currentUserLocation: {
        latitude: number;
        longitude: number;
    } | null;
    statistics: {
        total: number;
        pending: number;
        acknowledged: number;
        in_progress: number;
        critical: number;
        high: number;
    };
}

const props = defineProps<Props>();

let map: L.Map | null = null;
let markers: L.Marker[] = [];
let residentMarkers: L.Marker[] = [];
let staffMarkers: L.Marker[] = [];
let currentUserMarker: L.Marker | null = null;
const mapContainer = ref<HTMLElement | null>(null);
const selectedReport = ref<ActiveReport | null>(null);
const isTracking = ref(false);
let watchId: number | null = null;

// Fix Leaflet default icon issue
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const getSeverityColor = (severity: string): string => {
    switch (severity) {
        case 'critical':
            return '#dc2626'; // red-600
        case 'high':
            return '#ea580c'; // orange-600
        case 'medium':
            return '#ca8a04'; // yellow-600
        case 'low':
            return '#16a34a'; // green-600
        default:
            return '#6b7280'; // gray-500
    }
};

const getStatusIcon = (status: string): string => {
    switch (status) {
        case 'pending':
            return '⏳';
        case 'acknowledged':
            return '👁️';
        case 'in_progress':
            return '🔄';
        default:
            return '📍';
    }
};

const formatCalamityType = (type: string): string => {
    return type.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const createMarker = (report: ActiveReport): L.Marker => {
    const color = getSeverityColor(report.severity);
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background-color: ${color};
                width: 32px;
                height: 32px;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                border: 3px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <span style="
                    transform: rotate(45deg);
                    color: white;
                    font-size: 16px;
                    font-weight: bold;
                ">${getStatusIcon(report.status)}</span>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });

    const marker = L.marker([report.latitude, report.longitude], { icon })
        .bindPopup(`
            <div style="min-width: 200px;">
                <h3 style="font-weight: bold; margin-bottom: 8px; color: ${color};">${report.resident_name}</h3>
                <p style="margin: 4px 0;"><strong>Type:</strong> ${formatCalamityType(report.calamity_type)}</p>
                <p style="margin: 4px 0;"><strong>Severity:</strong> <span style="color: ${color};">${report.severity.toUpperCase()}</span></p>
                <p style="margin: 4px 0;"><strong>Status:</strong> ${report.status}</p>
                <p style="margin: 4px 0;"><strong>People:</strong> ${report.number_of_people}</p>
                ${report.needs && report.needs.length > 0 ? `<p style="margin: 4px 0;"><strong>Needs:</strong> ${report.needs.join(', ')}</p>` : ''}
                ${report.address ? `<p style="margin: 4px 0; font-size: 12px; color: #666;">${report.address}</p>` : ''}
                <a href="/staff/calamity/${report.id}" style="display: inline-block; margin-top: 8px; padding: 4px 12px; background: ${color}; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">View Details</a>
            </div>
        `);

    marker.on('click', () => {
        selectedReport.value = report;
    });

    return marker;
};

const initializeMap = () => {
    if (!mapContainer.value) return;

    // Default center: Barangay Purisima, Tago, Surigao del Sur (approximate coordinates)
    const defaultCenter: [number, number] = [8.5, 126.0];

    map = L.map(mapContainer.value, {
        center: defaultCenter,
        zoom: 13,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Add markers for all active reports
    updateMarkers();

    // Add current user marker if location exists
    if (props.currentUserLocation && map) {
        currentUserMarker = createCurrentUserMarker(props.currentUserLocation);
        currentUserMarker.addTo(map);
    }

    // Fit map to show all markers
    const allMarkers = [...markers, ...residentMarkers, ...staffMarkers];
    if (currentUserMarker) {
        allMarkers.push(currentUserMarker);
    }
    if (allMarkers.length > 0) {
        const group = new L.FeatureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

const createResidentMarker = (resident: ResidentLocation): L.Marker => {
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background-color: #3b82f6;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                border: 2px solid white;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            "></div>
        `,
        iconSize: [16, 16],
        iconAnchor: [8, 8],
        popupAnchor: [0, -8],
    });

    const marker = L.marker([resident.latitude, resident.longitude], { icon })
        .bindPopup(`
            <div style="min-width: 150px;">
                <h3 style="font-weight: bold; margin-bottom: 4px; color: #3b82f6;">${resident.name}</h3>
                <p style="margin: 2px 0; font-size: 12px; color: #666;">Resident</p>
                ${resident.purok ? `<p style="margin: 2px 0; font-size: 11px; color: #666;">Purok: ${resident.purok}</p>` : ''}
                ${resident.updated_at ? `<p style="margin: 2px 0; font-size: 11px; color: #999;">Updated: ${new Date(resident.updated_at).toLocaleString()}</p>` : ''}
            </div>
        `);

    return marker;
};

const createStaffMarker = (staff: StaffLocation): L.Marker => {
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background-color: #22c55e;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            "></div>
        `,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -10],
    });

    const marker = L.marker([staff.latitude, staff.longitude], { icon })
        .bindPopup(`
            <div style="min-width: 150px;">
                <h3 style="font-weight: bold; margin-bottom: 4px; color: #22c55e;">${staff.name}</h3>
                <p style="margin: 2px 0; font-size: 12px; color: #666;">Staff Member</p>
                ${staff.updated_at ? `<p style="margin: 2px 0; font-size: 11px; color: #999;">Updated: ${new Date(staff.updated_at).toLocaleString()}</p>` : ''}
            </div>
        `);

    return marker;
};

const createCurrentUserMarker = (location: { latitude: number; longitude: number }): L.Marker => {
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background-color: #ef4444;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.4);
            "></div>
        `,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
        popupAnchor: [0, -12],
    });

    return L.marker([location.latitude, location.longitude], { icon })
        .bindPopup('<div style="min-width: 150px;"><h3 style="font-weight: bold; color: #ef4444;">You</h3><p style="font-size: 12px; color: #666;">Your current location</p></div>');
};

const updateLocation = async (latitude: number, longitude: number) => {
    try {
        await axios.post('/staff/location/update', {
            latitude,
            longitude,
            location_shared: true,
        });
    } catch (error) {
        console.error('Failed to update location:', error);
        toast.error('Failed to update location');
    }
};

const startLocationTracking = () => {
    if (watchId !== null) {
        stopLocationTracking();
        return;
    }

    if (!navigator.geolocation) {
        toast.error('Geolocation is not supported by your browser.');
        return;
    }

    isTracking.value = true;

    watchId = navigator.geolocation.watchPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            updateLocation(lat, lng);

            // Update current user marker
            if (map) {
                if (currentUserMarker) {
                    currentUserMarker.setLatLng([lat, lng]);
                } else {
                    currentUserMarker = createCurrentUserMarker({ latitude: lat, longitude: lng });
                    currentUserMarker.addTo(map);
                }
            }
        },
        (error) => {
            console.error('Location tracking error:', error);
            isTracking.value = false;
            toast.error('Error tracking location');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }
    );

    toast.success('Location tracking started');
};

const stopLocationTracking = () => {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
        isTracking.value = false;
        toast.info('Location tracking stopped');
    }
};

const updateMarkers = () => {
    // Clear existing report markers
    markers.forEach(marker => {
        if (map) {
            map.removeLayer(marker);
        }
    });
    markers = [];

    // Clear existing resident markers
    residentMarkers.forEach(marker => {
        if (map) {
            map.removeLayer(marker);
        }
    });
    residentMarkers = [];

    // Clear existing staff markers (but not current user marker)
    staffMarkers.forEach(marker => {
        if (map) {
            map.removeLayer(marker);
        }
    });
    staffMarkers = [];

    // Add report markers
    props.activeReports.forEach(report => {
        if (report.latitude && report.longitude) {
            const marker = createMarker(report);
            if (map) {
                marker.addTo(map);
            }
            markers.push(marker);
        }
    });

    // Add resident location markers (blue)
    props.residentLocations.forEach(resident => {
        if (resident.latitude && resident.longitude) {
            const marker = createResidentMarker(resident);
            if (map) {
                marker.addTo(map);
            }
            residentMarkers.push(marker);
        }
    });

    // Add staff location markers (green)
    props.staffLocations.forEach(staff => {
        if (staff.latitude && staff.longitude) {
            const marker = createStaffMarker(staff);
            if (map) {
                marker.addTo(map);
            }
            staffMarkers.push(marker);
        }
    });
};

const refreshReports = () => {
    router.reload({ only: ['activeReports', 'residentLocations', 'staffLocations', 'currentUserLocation', 'statistics'] });
};

// Auto-refresh locations every 5 seconds for real-time updates
let refreshInterval: number | null = null;

onMounted(() => {
    initializeMap();
    
    // Start auto-refresh for real-time location updates
    refreshInterval = window.setInterval(() => {
        refreshReports();
    }, 5000); // Refresh every 5 seconds
});

onUnmounted(() => {
    stopLocationTracking();
    
    // Clear auto-refresh interval
    if (refreshInterval !== null) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    
    if (map) {
        map.remove();
        map = null;
    }
});

watch(() => [props.activeReports, props.residentLocations, props.staffLocations, props.currentUserLocation], () => {
    updateMarkers();
    if (props.currentUserLocation && map) {
        if (currentUserMarker) {
            currentUserMarker.setLatLng([props.currentUserLocation.latitude, props.currentUserLocation.longitude]);
        } else {
            currentUserMarker = createCurrentUserMarker(props.currentUserLocation);
            currentUserMarker.addTo(map);
        }
    }
    const allMarkers = [...markers, ...residentMarkers, ...staffMarkers];
    if (currentUserMarker) {
        allMarkers.push(currentUserMarker);
    }
    if (map && allMarkers.length > 0) {
        const group = new L.FeatureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}, { deep: true });
</script>

<template>
    <Head title="Calamity Map" />

    <StaffLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header -->
                <div class="mb-6">
                    <Link href="/staff/calamity">
                        <Button variant="ghost" size="sm" class="mb-4">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Reports
                        </Button>
                    </Link>

                    <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-orange-600 to-red-600 shadow-xl rounded-2xl">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                                <div class="text-white">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                            <MapPin class="h-8 w-8" />
                                        </div>
                                        <div>
                                            <h1 class="text-4xl font-bold">Calamity Map</h1>
                                            <p class="text-red-100 text-lg mt-1">Track all active emergency reports</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6 text-sm text-red-100">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                                            <span>{{ statistics.critical || 0 }} Critical</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-orange-400 rounded-full"></div>
                                            <span>{{ statistics.high || 0 }} High</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                            <span>{{ statistics.pending || 0 }} Pending</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                            <span>{{ statistics.in_progress || 0 }} In Progress</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            <span>{{ props.residentLocations.length }} Residents</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span>{{ props.staffLocations.length }} Staff</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Button 
                                        @click="isTracking ? stopLocationTracking() : startLocationTracking()"
                                        variant="outline" 
                                        class="bg-white/20 text-white border-white/30 hover:bg-white/30"
                                    >
                                        <Loader2 v-if="isTracking" class="h-4 w-4 mr-2 animate-spin" />
                                        <Navigation v-else class="h-4 w-4 mr-2" />
                                        {{ isTracking ? 'Stop Tracking' : 'Start Tracking' }}
                                    </Button>
                                    <Button @click="refreshReports" variant="outline" class="bg-white/20 text-white border-white/30 hover:bg-white/30">
                                        <RefreshCw class="h-4 w-4 mr-2" />
                                        Refresh
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Map -->
                    <div class="lg:col-span-3">
                        <Card class="shadow-lg border-gray-200 p-0 overflow-hidden">
                            <div ref="mapContainer" class="w-full h-[600px] rounded-lg"></div>
                        </Card>
                    </div>

                    <!-- Sidebar - Active Reports List -->
                    <div class="space-y-4">
                        <Card class="shadow-lg border-gray-200">
                            <CardHeader>
                                <CardTitle class="text-lg">Active Reports</CardTitle>
                                <CardDescription>{{ props.activeReports.length }} reports on map</CardDescription>
                            </CardHeader>
                            <CardContent class="p-0">
                                <div class="max-h-[600px] overflow-y-auto">
                                    <div
                                        v-for="report in props.activeReports"
                                        :key="report.id"
                                        @click="selectedReport = report"
                                        :class="[
                                            'p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors',
                                            selectedReport?.id === report.id ? 'bg-blue-50 border-blue-200' : ''
                                        ]"
                                    >
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="font-semibold text-gray-900 text-sm">{{ report.resident_name }}</h4>
                                            <Badge
                                                :style="{ backgroundColor: getSeverityColor(report.severity), color: 'white' }"
                                                class="text-xs capitalize"
                                            >
                                                {{ report.severity }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-2 capitalize">{{ formatCalamityType(report.calamity_type) }}</p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <Users class="h-3 w-3" />
                                            <span>{{ report.number_of_people }} people</span>
                                        </div>
                                        <div v-if="report.needs && report.needs.length > 0" class="mt-2 flex flex-wrap gap-1">
                                            <Badge
                                                v-for="need in report.needs.slice(0, 2)"
                                                :key="need"
                                                variant="outline"
                                                class="text-xs capitalize"
                                            >
                                                {{ need }}
                                            </Badge>
                                        </div>
                                        <Link :href="`/staff/calamity/${report.id}`" class="mt-2 inline-block">
                                            <Button variant="ghost" size="sm" class="h-6 text-xs">
                                                View Details →
                                            </Button>
                                        </Link>
                                    </div>
                                    <div v-if="props.activeReports.length === 0" class="p-8 text-center">
                                        <MapPin class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <p class="text-gray-500 text-sm">No active reports on map</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

<style>
.custom-marker {
    background: transparent !important;
    border: none !important;
}

.leaflet-popup-content-wrapper {
    border-radius: 8px;
}

.leaflet-popup-content {
    margin: 12px;
}
</style>

