<template>
    <div class="min-h-full bg-gray-100">

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#1a56db] via-[#1e40af] to-[#7c3aed]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="pointer-events-none absolute -bottom-10 left-1/2 h-56 w-56 -translate-x-1/2 rounded-full bg-purple-400/15" />

            <div class="relative px-5 pt-6 pb-5">
                <p class="text-[13px] font-medium text-blue-200/90">Admin Panel</p>
                <h1 class="mt-0.5 text-[22px] font-extrabold leading-tight tracking-tight text-white">
                    Barangay Overview
                </h1>
            </div>

            <!-- Stats row -->
            <div class="relative mx-4 mb-5 overflow-hidden rounded-2xl border border-white/10 bg-white/10 backdrop-blur-sm">
                <div class="grid grid-cols-4 divide-x divide-white/10">
                    <div v-for="s in heroStats" :key="s.label" class="flex flex-col items-center gap-0.5 py-3.5">
                        <span class="text-[24px] font-extrabold leading-none text-white">{{ s.value }}</span>
                        <span class="text-[11px] font-medium text-white/60">{{ s.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="px-4 pb-10 pt-4 space-y-4 md:px-5 md:pt-5 lg:grid lg:grid-cols-2 lg:gap-5 lg:space-y-0">

            <!-- Revenue card -->
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50">
                        <PhilippinePeso class="h-5 w-5 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-[12px] text-gray-400 font-medium">Monthly Revenue</p>
                        <p class="text-[20px] font-extrabold text-gray-900">₱{{ formattedRevenue }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-amber-50 p-3 text-center">
                        <p class="text-[22px] font-extrabold text-amber-700">{{ stats.pending_tx }}</p>
                        <p class="text-[11px] text-amber-600 font-medium">Pending</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-3 text-center">
                        <p class="text-[22px] font-extrabold text-emerald-700">{{ stats.completed_tx }}</p>
                        <p class="text-[11px] text-emerald-600 font-medium">Completed</p>
                    </div>
                </div>
            </div>

            <!-- Recent Staff -->
            <div>
                <div class="mb-3 flex items-center justify-between px-0.5">
                    <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-400">Recent Staff</h2>
                    <Link :href="staffUrl" class="text-[13px] font-semibold text-blue-600">View all →</Link>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div v-if="recentStaff.length === 0" class="p-6 text-center text-[13px] text-gray-400">
                        No staff accounts yet.
                    </div>
                    <div
                        v-for="s in recentStaff"
                        :key="s.id"
                        class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                            <span class="text-[13px] font-bold text-blue-600">{{ initials(s.name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-gray-900">{{ s.name }}</p>
                            <p class="truncate text-[12px] text-gray-400">{{ s.email }}</p>
                        </div>
                        <span class="shrink-0 text-[11px] text-gray-300">{{ s.created_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Residents -->
            <div class="lg:col-span-2">
                <div class="mb-3 flex items-center justify-between px-0.5">
                    <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-400">Recent Residents</h2>
                    <Link :href="residentsUrl" class="text-[13px] font-semibold text-blue-600">View all →</Link>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div v-if="recentResidents.length === 0" class="p-6 text-center text-[13px] text-gray-400">
                        No residents yet.
                    </div>
                    <div
                        v-for="r in recentResidents"
                        :key="r.id"
                        class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-50">
                            <span class="text-[13px] font-bold text-gray-500">{{ initials(r.name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-gray-900">{{ r.name }}</p>
                            <p class="truncate text-[12px] text-gray-400">{{ r.purok ?? 'No purok' }} · {{ r.email }}</p>
                        </div>
                        <span class="shrink-0 text-[11px] text-gray-300">{{ r.created_at }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import { PhilippinePeso } from 'lucide-vue-next';

defineOptions({ name: 'AdminDashboard', layout: AdminLayout });

interface Stats {
    total_residents: number;
    total_staff: number;
    total_puroks: number;
    total_tx: number;
    pending_tx: number;
    completed_tx: number;
    monthly_revenue: number;
}
interface StaffItem  { id: number; name: string; email: string; created_at: string; }
interface ResidentItem { id: number; name: string; email: string; purok: string | null; created_at: string; }

const props = defineProps<{
    stats: Stats;
    recentStaff: StaffItem[];
    recentResidents: ResidentItem[];
}>();

const staffUrl     = admin.staff.index().url;
const residentsUrl = admin.residents.index().url;

const heroStats = computed(() => [
    { label: 'Residents', value: props.stats.total_residents },
    { label: 'Staff',     value: props.stats.total_staff     },
    { label: 'Puroks',    value: props.stats.total_puroks    },
    { label: 'Requests',  value: props.stats.total_tx        },
]);

const formattedRevenue = computed(() =>
    new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(props.stats.monthly_revenue)
);

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}
</script>
