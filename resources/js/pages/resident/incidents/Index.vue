<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { AlertTriangle, Clock, CheckCircle, AlertCircle, Eye, Loader2, MapPin, Users, ChevronLeft, X } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useUtils } from '@/composables/useUtils';
import { toast } from 'vue-sonner';

interface IncidentReport {
    id: number;
    incident_type: string;
    severity: string;
    status: string;
    description: string;
    needs: string[];
    number_of_people: number;
    created_at: string;
    acknowledged_at?: string;
    assisted_at?: string;
    staff?: { name: string };
}

interface Props {
    reports: {
        data: IncidentReport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: { status?: string };
}

const props = defineProps<Props>();
const { formatDateShort } = useUtils();

// ── Wizard state ────────────────────────────────────────────────────────────
const wizardOpen = ref(false);
const step = ref(1);

// ── Incident type catalogue ─────────────────────────────────────────────────
const incidentTypes = [
    { value: 'fire',              label: 'Fire',       emoji: '🔥', severity: 'critical', color: '#EF4444' },
    { value: 'flood',             label: 'Flood',      emoji: '🌊', severity: 'high',     color: '#3B82F6' },
    { value: 'typhoon',           label: 'Typhoon',    emoji: '🌀', severity: 'high',     color: '#6366F1' },
    { value: 'earthquake',        label: 'Earthquake', emoji: '🌍', severity: 'critical', color: '#F97316' },
    { value: 'medical_emergency', label: 'Medical',    emoji: '🏥', severity: 'critical', color: '#EC4899' },
    { value: 'accident',          label: 'Accident',   emoji: '🚗', severity: 'high',     color: '#EAB308' },
    { value: 'crime',             label: 'Crime',      emoji: '🚨', severity: 'high',     color: '#8B5CF6' },
    { value: 'landslide',         label: 'Landslide',  emoji: '⛰️',  severity: 'high',     color: '#A16207' },
    { value: 'other',             label: 'Other',      emoji: '🆘', severity: 'medium',   color: '#6B7280' },
] as const;

type IncidentType = typeof incidentTypes[number];

const vulnerableGroups = [
    { key: 'has_elderly'  as const, label: 'Elderly',  emoji: '👴' },
    { key: 'has_children' as const, label: 'Children', emoji: '👶' },
    { key: 'has_pwd'      as const, label: 'PWD',      emoji: '♿' },
    { key: 'has_pregnant' as const, label: 'Pregnant', emoji: '🤰' },
];

const needOptions = [
    { value: 'food',           label: 'Food',       emoji: '🍱' },
    { value: 'water',          label: 'Water',      emoji: '💧' },
    { value: 'medicine',       label: 'Medicine',   emoji: '💊' },
    { value: 'evacuation',     label: 'Evacuation', emoji: '🚌' },
    { value: 'rescue',         label: 'Rescue',     emoji: '🚒' },
    { value: 'shelter',        label: 'Shelter',    emoji: '🏠' },
    { value: 'transportation', label: 'Transport',  emoji: '🚗' },
    { value: 'other',          label: 'Other',      emoji: '📋' },
];

// ── Location ─────────────────────────────────────────────────────────────────
const locationLoading = ref(false);
const locationError = ref<string | null>(null);
const locationPermissionStatus = ref<'prompt' | 'granted' | 'denied' | 'unknown'>('unknown');

const checkLocationPermission = async () => {
    if (!navigator.geolocation) { locationPermissionStatus.value = 'denied'; return; }
    if ('permissions' in navigator) {
        try {
            const result = await navigator.permissions.query({ name: 'geolocation' as PermissionName });
            locationPermissionStatus.value = result.state as typeof locationPermissionStatus.value;
            result.onchange = () => { locationPermissionStatus.value = result.state as typeof locationPermissionStatus.value; };
        } catch { locationPermissionStatus.value = 'prompt'; }
    } else {
        locationPermissionStatus.value = 'prompt';
    }
};

const getCurrentLocation = () => {
    locationLoading.value = true;
    locationError.value = null;
    if (!navigator.geolocation) {
        locationError.value = 'Geolocation not supported.';
        locationLoading.value = false;
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude;
            form.longitude = pos.coords.longitude;
            form.address = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
            locationLoading.value = false;
            locationPermissionStatus.value = 'granted';
        },
        (err) => {
            locationLoading.value = false;
            if (err.code === err.PERMISSION_DENIED) {
                locationPermissionStatus.value = 'denied';
                locationError.value = 'Location access denied. Please enable it in your browser settings.';
            } else if (err.code === err.POSITION_UNAVAILABLE) {
                locationError.value = 'Location unavailable. Check your device GPS.';
            } else {
                locationError.value = 'Location timed out. Tap retry.';
            }
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
    );
};

// ── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
    latitude: null as number | null,
    longitude: null as number | null,
    address: '',
    location_notes: '',
    incident_type: 'other',
    severity: 'medium',
    description: '',
    needs: [] as string[],
    specific_needs: '',
    number_of_people: 1,
    has_elderly: false,
    has_children: false,
    has_pwd: false,
    has_pregnant: false,
    has_pregnant_: false,
    medical_conditions: '',
    location_shared: true,
});

// ── Wizard helpers ────────────────────────────────────────────────────────────
const selectedType = computed(() =>
    incidentTypes.find(t => t.value === form.incident_type) ?? incidentTypes[incidentTypes.length - 1]
);

const selectType = (type: IncidentType) => {
    form.incident_type = type.value;
    form.severity = type.severity;
    step.value = 2;
};

const toggleNeed = (val: string) => {
    const i = form.needs.indexOf(val);
    if (i > -1) form.needs.splice(i, 1);
    else form.needs.push(val);
};

const toggleVulnerable = (key: 'has_elderly' | 'has_children' | 'has_pwd' | 'has_pregnant') => {
    form[key] = !form[key];
};

const openWizard = async () => {
    form.reset();
    form.number_of_people = 1;
    step.value = 1;
    wizardOpen.value = true;
    await checkLocationPermission();
    getCurrentLocation();
};

const closeWizard = () => {
    wizardOpen.value = false;
    locationError.value = null;
};

const submitReport = () => {
    if (!form.latitude && !form.longitude) {
        toast.warning('Sending without GPS location. Responders may need more details.');
    }
    form.post('/resident/incidents', {
        onSuccess: () => {
            toast.success('SOS sent! Help is on the way.');
            closeWizard();
        },
        onError: () => {
            toast.error('Failed to submit. Please try again.');
        },
    });
};

// ── Reports list helpers ──────────────────────────────────────────────────────
const filterForm = useForm({ status: props.filters.status || 'all' });

const getStatusIcon = (status: string) => {
    if (status === 'pending') return Clock;
    if (status === 'acknowledged' || status === 'in_progress') return AlertCircle;
    return CheckCircle;
};

const getStatusColor = (status: string) => {
    const map: Record<string, string> = {
        pending:     'bg-yellow-100 text-yellow-800',
        acknowledged:'bg-blue-100 text-blue-800',
        in_progress: 'bg-indigo-100 text-indigo-800',
        assisted:    'bg-green-100 text-green-800',
        resolved:    'bg-gray-100 text-gray-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};

const fmt = (s: string) => s.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' ');

onMounted(() => checkLocationPermission());
</script>

<template>
    <Head title="Emergency Assistance" />

    <ResidentLayout>
        <div class="w-full min-h-full flex flex-col">

            <!-- ── Hero / Get Help button ───────────────────────────────── -->
            <div class="relative flex-1 flex items-center justify-center px-4 py-8 min-h-[55vh] overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900">
                    <div class="absolute inset-0 overflow-hidden">
                        <div v-for="i in 7" :key="i"
                            class="ripple-circle absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-cyan-400/30"
                            :style="{ width: `${150 + i * 90}px`, height: `${150 + i * 90}px`, animationDelay: `${i * 0.4}s` }" />
                    </div>
                </div>

                <div class="relative z-10 text-center w-full max-w-sm">
                    <button @click="openWizard"
                        class="group relative w-52 h-52 sm:w-64 sm:h-64 mx-auto rounded-full bg-white shadow-2xl
                               hover:shadow-cyan-500/50 transition-all duration-300 hover:scale-105
                               active:scale-95 focus:outline-none focus:ring-4 focus:ring-cyan-400/50">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-cyan-400/20 to-blue-500/20 blur-xl group-hover:blur-2xl transition-all" />
                        <div class="absolute inset-0 rounded-full bg-cyan-400/20 animate-ping" />
                        <div class="relative flex flex-col items-center justify-center h-full z-10 gap-2">
                            <span class="text-5xl sm:text-6xl leading-none select-none">🆘</span>
                            <span class="text-cyan-600 font-extrabold text-xl sm:text-2xl tracking-wide">GET HELP</span>
                        </div>
                    </button>
                    <p class="mt-6 text-white/90 text-base font-semibold">Tap to report an emergency</p>
                    <p class="mt-1 text-white/60 text-sm">Your location will be shared with responders</p>
                </div>
            </div>

            <!-- ── Reports list ─────────────────────────────────────────── -->
            <div class="bg-white/95 backdrop-blur-sm rounded-t-3xl -mt-6 px-4 py-6 min-h-[45vh]">
                <div class="max-w-2xl mx-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900">Your Reports</h2>
                        <Link href="/resident/incidents/map"
                            class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-xl px-3 py-1.5">
                            <MapPin class="h-3.5 w-3.5" /> Map
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div v-if="props.reports.data.length === 0"
                            class="flex flex-col items-center justify-center py-14 text-center">
                            <span class="text-5xl mb-3">📋</span>
                            <p class="font-semibold text-gray-700">No reports yet</p>
                            <p class="text-sm text-gray-400 mt-1">Tap the button above to report an emergency</p>
                        </div>

                        <div v-for="report in props.reports.data" :key="report.id"
                            class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                            <div class="flex items-center gap-3 px-4 py-3.5">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg leading-none">
                                            {{ incidentTypes.find(t => t.value === report.incident_type)?.emoji ?? '🆘' }}
                                        </span>
                                        <p class="font-bold text-gray-900 text-sm truncate">{{ fmt(report.incident_type) }}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ formatDateShort(report.created_at) }} · {{ report.number_of_people }} {{ report.number_of_people === 1 ? 'person' : 'people' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase"
                                    :class="getStatusColor(report.status)">
                                    {{ fmt(report.status) }}
                                </span>
                                <Link :href="`/resident/incidents/${report.id}`"
                                    class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </div>
                            <div v-if="report.needs?.length" class="px-4 pb-3 flex flex-wrap gap-1.5">
                                <span v-for="n in report.needs" :key="n"
                                    class="text-[10px] font-semibold bg-gray-100 text-gray-600 rounded-full px-2.5 py-0.5 capitalize">
                                    {{ n }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.reports.last_page > 1"
                        class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100 text-sm text-gray-500">
                        <span>{{ props.reports.from }}–{{ props.reports.to }} of {{ props.reports.total }}</span>
                        <div class="flex gap-2">
                            <Link v-if="props.reports.current_page > 1"
                                :href="`/resident/incidents?page=${props.reports.current_page - 1}`"
                                class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-medium hover:bg-gray-50">
                                ← Prev
                            </Link>
                            <Link v-if="props.reports.current_page < props.reports.last_page"
                                :href="`/resident/incidents?page=${props.reports.current_page + 1}`"
                                class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-medium hover:bg-gray-50">
                                Next →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             SOS WIZARD — full-screen, 3-step
        ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="wizard-slide">
                <div v-if="wizardOpen"
                    class="fixed inset-0 z-[200] bg-[#0d0d0d] flex flex-col select-none"
                    style="padding-bottom: env(safe-area-inset-bottom, 0px)">

                    <!-- Top bar: back/close + progress -->
                    <div class="flex items-center gap-3 px-4 pb-3 shrink-0"
                        style="padding-top: max(1rem, env(safe-area-inset-top, 1rem))">
                        <button
                            @click="step > 1 ? step-- : closeWizard()"
                            class="w-9 h-9 flex items-center justify-center rounded-full bg-white/10 text-white active:scale-90 transition-transform">
                            <ChevronLeft v-if="step > 1" class="h-5 w-5" />
                            <X v-else class="h-5 w-5" />
                        </button>
                        <div class="flex-1 flex gap-1.5">
                            <div v-for="s in 3" :key="s"
                                class="h-1 flex-1 rounded-full transition-all duration-400"
                                :class="s <= step ? 'bg-red-500' : 'bg-white/15'" />
                        </div>
                        <span class="text-xs text-white/30 font-medium w-10 text-right">{{ step }}/3</span>
                    </div>

                    <!-- ── STEP 1: What's happening? ─────────────────────── -->
                    <Transition name="step-fade" mode="out-in">
                    <div v-if="step === 1" key="step1" class="flex-1 overflow-y-auto px-4 pb-6">
                        <div class="py-3 mb-1">
                            <p class="text-[11px] font-bold tracking-widest text-red-400 uppercase">What's happening?</p>
                            <h2 class="text-white text-[22px] font-extrabold leading-tight mt-0.5">Tap your emergency type</h2>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5">
                            <button v-for="type in incidentTypes" :key="type.value"
                                @click="selectType(type)"
                                class="flex flex-col items-center justify-center gap-2 rounded-2xl py-5 px-2 border-2 active:scale-95 transition-transform duration-100"
                                :style="{
                                    backgroundColor: type.color + '18',
                                    borderColor: type.color + '55',
                                }">
                                <span class="text-4xl leading-none">{{ type.emoji }}</span>
                                <span class="text-white text-[11px] font-bold text-center leading-tight">{{ type.label }}</span>
                            </button>
                        </div>

                        <!-- Location capture status -->
                        <div class="mt-5 rounded-2xl px-4 py-3 flex items-center gap-3 border"
                            :class="form.latitude
                                ? 'bg-green-500/10 border-green-500/25'
                                : 'bg-white/5 border-white/10'">
                            <Loader2 v-if="locationLoading" class="h-4 w-4 text-white/40 animate-spin shrink-0" />
                            <CheckCircle v-else-if="form.latitude" class="h-4 w-4 text-green-400 shrink-0" />
                            <MapPin v-else class="h-4 w-4 text-white/25 shrink-0" />
                            <p class="text-xs" :class="form.latitude ? 'text-green-400' : 'text-white/35'">
                                {{ locationLoading ? 'Capturing your location in the background…'
                                 : form.latitude ? 'Location ready ✓'
                                 : locationError ?? 'Location not yet captured' }}
                            </p>
                        </div>
                    </div>
                    </Transition>

                    <!-- ── STEP 2: Who needs help? ────────────────────────── -->
                    <Transition name="step-fade" mode="out-in">
                    <div v-if="step === 2" key="step2" class="flex-1 overflow-y-auto px-4 pb-6">
                        <div class="py-3 mb-4">
                            <p class="text-[11px] font-bold tracking-widest text-red-400 uppercase">Who needs help?</p>
                            <h2 class="text-white text-[22px] font-extrabold leading-tight mt-0.5">People & needs</h2>
                        </div>

                        <!-- Incident type reminder chip -->
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-3.5 py-1.5 mb-6">
                            <span class="text-lg leading-none">{{ selectedType.emoji }}</span>
                            <span class="text-white text-sm font-semibold">{{ selectedType.label }}</span>
                        </div>

                        <!-- People counter -->
                        <p class="text-white/50 text-[11px] font-bold uppercase tracking-widest mb-3">Number of people</p>
                        <div class="flex items-center justify-between bg-white/6 rounded-2xl px-5 py-4 mb-7">
                            <button
                                @click="form.number_of_people = Math.max(1, form.number_of_people - 1)"
                                class="w-14 h-14 rounded-full bg-white/10 text-white text-3xl font-thin flex items-center justify-center active:scale-90 transition-transform">
                                −
                            </button>
                            <span class="text-white text-6xl font-extrabold tabular-nums">{{ form.number_of_people }}</span>
                            <button
                                @click="form.number_of_people = Math.min(999, form.number_of_people + 1)"
                                class="w-14 h-14 rounded-full bg-red-600 text-white text-3xl font-thin flex items-center justify-center active:scale-90 transition-transform shadow-lg shadow-red-500/30">
                                +
                            </button>
                        </div>

                        <!-- Vulnerable groups -->
                        <p class="text-white/50 text-[11px] font-bold uppercase tracking-widest mb-3">
                            Vulnerable persons present?
                        </p>
                        <div class="grid grid-cols-2 gap-2.5 mb-7">
                            <button v-for="g in vulnerableGroups" :key="g.key"
                                @click="toggleVulnerable(g.key)"
                                class="flex items-center gap-3 rounded-2xl px-4 py-4 border-2 active:scale-95 transition-all duration-150"
                                :class="form[g.key]
                                    ? 'bg-red-500/15 border-red-500 text-white'
                                    : 'bg-white/5 border-white/10 text-white/55'">
                                <span class="text-2xl leading-none">{{ g.emoji }}</span>
                                <span class="text-sm font-bold">{{ g.label }}</span>
                                <span v-if="form[g.key]" class="ml-auto text-red-400 text-xs font-bold">✓</span>
                            </button>
                        </div>

                        <!-- Needs -->
                        <p class="text-white/50 text-[11px] font-bold uppercase tracking-widest mb-3">
                            What do you need? <span class="text-white/25 normal-case font-normal text-[10px]">optional</span>
                        </p>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <button v-for="n in needOptions" :key="n.value"
                                @click="toggleNeed(n.value)"
                                class="flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold border active:scale-95 transition-all"
                                :class="form.needs.includes(n.value)
                                    ? 'bg-red-500/20 border-red-500 text-white'
                                    : 'bg-white/5 border-white/12 text-white/55'">
                                <span>{{ n.emoji }}</span>{{ n.label }}
                            </button>
                        </div>

                        <button @click="step = 3"
                            class="w-full bg-red-600 active:bg-red-700 text-white rounded-2xl py-4 font-extrabold text-base active:scale-[0.98] transition-transform shadow-lg shadow-red-500/25">
                            Continue →
                        </button>
                    </div>
                    </Transition>

                    <!-- ── STEP 3: Confirm & Send ─────────────────────────── -->
                    <Transition name="step-fade" mode="out-in">
                    <div v-if="step === 3" key="step3" class="flex-1 overflow-y-auto px-4 pb-6">
                        <div class="py-3 mb-4">
                            <p class="text-[11px] font-bold tracking-widest text-red-400 uppercase">Ready to send</p>
                            <h2 class="text-white text-[22px] font-extrabold leading-tight mt-0.5">Review your report</h2>
                        </div>

                        <!-- Summary card -->
                        <div class="rounded-2xl bg-white/6 border border-white/10 p-4 mb-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="text-4xl leading-none">{{ selectedType.emoji }}</span>
                                <div>
                                    <p class="text-white font-extrabold text-lg">{{ selectedType.label }}</p>
                                    <p class="text-white/40 text-xs uppercase tracking-wide">{{ form.severity }} severity</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-white/65 text-sm flex-wrap">
                                <span>👥 {{ form.number_of_people }} {{ form.number_of_people === 1 ? 'person' : 'people' }}</span>
                                <template v-if="form.has_elderly || form.has_children || form.has_pwd || form.has_pregnant">
                                    <span class="text-white/25">·</span>
                                    <span v-if="form.has_elderly">👴</span>
                                    <span v-if="form.has_children">👶</span>
                                    <span v-if="form.has_pwd">♿</span>
                                    <span v-if="form.has_pregnant">🤰</span>
                                </template>
                            </div>
                            <div v-if="form.needs.length > 0" class="flex flex-wrap gap-1.5">
                                <span v-for="n in form.needs" :key="n"
                                    class="text-[11px] font-semibold bg-white/10 text-white/65 rounded-full px-2.5 py-1">
                                    {{ needOptions.find(o => o.value === n)?.emoji }}
                                    {{ needOptions.find(o => o.value === n)?.label }}
                                </span>
                            </div>
                        </div>

                        <!-- Location status -->
                        <div class="rounded-2xl p-4 mb-4 flex items-start gap-3 border"
                            :class="{
                                'bg-green-500/12 border-green-500/30': form.latitude,
                                'bg-amber-500/12 border-amber-500/30': locationLoading && !form.latitude,
                                'bg-red-500/12 border-red-500/30': !form.latitude && !locationLoading,
                            }">
                            <Loader2 v-if="locationLoading && !form.latitude" class="h-5 w-5 text-amber-400 animate-spin shrink-0 mt-0.5" />
                            <CheckCircle v-else-if="form.latitude" class="h-5 w-5 text-green-400 shrink-0 mt-0.5" />
                            <AlertTriangle v-else class="h-5 w-5 text-red-400 shrink-0 mt-0.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold"
                                    :class="form.latitude ? 'text-green-300' : locationLoading ? 'text-amber-300' : 'text-red-300'">
                                    {{ form.latitude ? 'Location ready' : locationLoading ? 'Capturing location…' : 'No location' }}
                                </p>
                                <p v-if="form.latitude" class="text-xs text-green-400/60 mt-0.5 font-mono">
                                    {{ form.latitude?.toFixed(5) }}, {{ form.longitude?.toFixed(5) }}
                                </p>
                                <p v-else-if="!locationLoading" class="text-xs text-red-400/70 mt-0.5 leading-relaxed">
                                    {{ locationError ?? 'Enable location access in your browser settings.' }}
                                </p>
                                <button v-if="!form.latitude && !locationLoading"
                                    @click="getCurrentLocation"
                                    class="mt-2 text-xs font-bold text-red-300 underline underline-offset-2 active:opacity-70">
                                    Tap to retry
                                </button>
                            </div>
                        </div>

                        <!-- Optional notes -->
                        <div class="mb-6">
                            <label class="text-white/50 text-[11px] font-bold uppercase tracking-widest block mb-2">
                                Additional notes
                                <span class="text-white/25 normal-case font-normal text-[10px]"> optional</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                placeholder="Any details that can help responders arrive faster…"
                                rows="3"
                                class="w-full rounded-2xl bg-white/6 border border-white/10 text-white text-sm
                                       placeholder:text-white/25 px-4 py-3 resize-none outline-none
                                       focus:border-red-500/50 focus:bg-white/9 transition-colors" />
                        </div>

                        <!-- Send SOS -->
                        <button @click="submitReport"
                            :disabled="form.processing"
                            class="w-full rounded-2xl py-5 font-extrabold text-lg text-white flex items-center justify-center gap-3
                                   active:scale-[0.97] transition-transform disabled:opacity-50 shadow-2xl"
                            :class="form.latitude
                                ? 'bg-red-600 active:bg-red-700 shadow-red-500/30'
                                : 'bg-red-800 shadow-red-900/30'">
                            <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                            <template v-else>
                                <span class="text-2xl">🆘</span>
                                <span>{{ locationLoading && !form.latitude ? 'Wait for location…' : 'Send SOS' }}</span>
                            </template>
                        </button>

                        <p v-if="!form.latitude && !locationLoading" class="text-center mt-3">
                            <button @click="submitReport" :disabled="form.processing"
                                class="text-xs text-white/30 underline underline-offset-2">
                                Send without location
                            </button>
                        </p>
                    </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </ResidentLayout>
</template>

<style scoped>
/* Hero ripple animation */
@keyframes ripple {
    0%   { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
}
.ripple-circle { animation: ripple 4s ease-out infinite; }

/* Wizard slides up from bottom */
.wizard-slide-enter-active { transition: transform 0.38s cubic-bezier(0.32, 0.72, 0, 1); }
.wizard-slide-leave-active  { transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1); }
.wizard-slide-enter-from,
.wizard-slide-leave-to      { transform: translateY(100%); }

/* Step-to-step content fade */
.step-fade-enter-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.step-fade-leave-active { transition: opacity 0.12s ease; }
.step-fade-enter-from   { opacity: 0; transform: translateX(16px); }
.step-fade-leave-to     { opacity: 0; }
</style>
