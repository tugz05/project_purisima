<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { MapPin, ArrowLeft, Users, Navigation, RefreshCw } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

interface StaffLocation {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    type: 'staff';
    updated_at?: string;
}

interface ResidentLocation {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    type: 'resident';
    updated_at?: string;
}

interface Props {
    staffLocations: StaffLocation[];
    residentLocations: ResidentLocation[];
    currentUserLocation: {
        latitude: number;
        longitude: number;
    } | null;
}

const props = defineProps<Props>();

let map: L.Map | null = null;
let staffMarkers: L.Marker[] = [];
let residentMarkers: L.Marker[] = [];
let currentUserMarker: L.Marker | null = null;
const mapContainer = ref<HTMLElement | null>(null);
const isTracking = ref(false);
let watchId: number | null = null;

// Fix Leaflet default icon issue
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

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
                ${resident.updated_at ? `<p style="margin: 2px 0; font-size: 11px; color: #999;">Updated: ${new Date(resident.updated_at).toLocaleString()}</p>` : ''}
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
        await axios.post('/resident/location/update', {
            latitude,
            longitude,
            location_shared: true,
        });
    } catch (error) {
        console.error('Failed to update location:', error);
    }
};

const startLocationTracking = () => {
    if (watchId !== null) {
        stopLocationTracking();
        return;
    }

    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
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
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }
    );
};

const stopLocationTracking = () => {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
        isTracking.value = false;
    }
};

const initializeMap = () => {
    if (!mapContainer.value) return;

    // Default center or use current user location
    let center: [number, number] = [8.5, 126.0];
    let zoom = 13;

    if (props.currentUserLocation) {
        center = [props.currentUserLocation.latitude, props.currentUserLocation.longitude];
        zoom = 15;
    }

    map = L.map(mapContainer.value, {
        center,
        zoom,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Add markers
    updateMarkers();

    // Fit map to show all markers
    const allMarkers: L.Marker[] = [...staffMarkers, ...residentMarkers];
    if (currentUserMarker) {
        allMarkers.push(currentUserMarker);
    }
    if (allMarkers.length > 0) {
        const group = new L.FeatureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

const updateMarkers = () => {
    // Clear existing markers
    staffMarkers.forEach(marker => {
        if (map) map.removeLayer(marker);
    });
    staffMarkers = [];

    residentMarkers.forEach(marker => {
        if (map) map.removeLayer(marker);
    });
    residentMarkers = [];

    // Add staff markers (green)
    props.staffLocations.forEach(staff => {
        if (staff.latitude && staff.longitude) {
            const marker = createStaffMarker(staff);
            if (map) {
                marker.addTo(map);
            }
            staffMarkers.push(marker);
        }
    });

    // Add resident markers (blue)
    props.residentLocations.forEach(resident => {
        if (resident.latitude && resident.longitude) {
            const marker = createResidentMarker(resident);
            if (map) {
                marker.addTo(map);
            }
            residentMarkers.push(marker);
        }
    });

    // Add current user marker (red)
    if (props.currentUserLocation && map) {
        currentUserMarker = createCurrentUserMarker(props.currentUserLocation);
        currentUserMarker.addTo(map);
    }
};

const refreshLocations = () => {
    router.reload({ only: ['staffLocations', 'residentLocations', 'currentUserLocation'] });
};

// Auto-refresh locations every 5 seconds for real-time updates
let refreshInterval: number | null = null;

onMounted(() => {
    initializeMap();
    
    // Start auto-refresh for real-time location updates
    refreshInterval = window.setInterval(() => {
        refreshLocations();
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

watch(() => [props.staffLocations, props.residentLocations, props.currentUserLocation], () => {
    if (map) {
        updateMarkers();
        const allMarkers: L.Marker[] = [...staffMarkers, ...residentMarkers];
        if (currentUserMarker) {
            allMarkers.push(currentUserMarker);
        }
        if (allMarkers.length > 0) {
            const group = new L.FeatureGroup(allMarkers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }
}, { deep: true });
</script>

<template>
    <Head title="Location Map" />

    <ResidentLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header -->
                <div class="mb-6">
                    <Link href="/resident/calamity">
                        <Button variant="ghost" size="sm" class="mb-4">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Reports
                        </Button>
                    </Link>

                    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 shadow-xl rounded-2xl">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                                <div class="text-white">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                                            <MapPin class="h-8 w-8" />
                                        </div>
                                        <div>
                                            <h1 class="text-4xl font-bold">Location Map</h1>
                                            <p class="text-blue-100 text-lg mt-1">View staff and residents on the map</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6 text-sm text-blue-100">
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                                            <span>{{ props.staffLocations.length }} Staff</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 bg-blue-500 rounded-full border-2 border-white"></div>
                                            <span>{{ props.residentLocations.length }} Residents</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 bg-red-500 rounded-full border-2 border-white"></div>
                                            <span>You</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Button 
                                        @click="isTracking ? stopLocationTracking() : startLocationTracking()"
                                        variant="outline" 
                                        class="bg-white/20 text-white border-white/30 hover:bg-white/30"
                                    >
                                        <Navigation class="h-4 w-4 mr-2" />
                                        {{ isTracking ? 'Stop Tracking' : 'Start Tracking' }}
                                    </Button>
                                    <Button @click="refreshLocations" variant="outline" class="bg-white/20 text-white border-white/30 hover:bg-white/30">
                                        <RefreshCw class="h-4 w-4 mr-2" />
                                        Refresh
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <Card class="shadow-lg border-gray-200 p-0 overflow-hidden">
                    <div ref="mapContainer" class="w-full h-[600px] rounded-lg"></div>
                </Card>
            </div>
        </div>
    </ResidentLayout>
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

