<template>
    <div class="min-h-full bg-gray-100">

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#7c3aed] via-[#6d28d9] to-[#4f46e5]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="relative px-5 pt-6 pb-6">
                <p class="text-[13px] font-medium text-purple-200/80">Admin</p>
                <h1 class="mt-0.5 text-[22px] font-extrabold leading-tight tracking-tight text-white">
                    Residents
                </h1>
                <p class="mt-1 text-[13px] text-purple-200/70">{{ residents.total }} registered resident{{ residents.total !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-4 pt-4 pb-2 md:px-5 md:pt-5 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                    type="text"
                    :value="filterSearch"
                    @input="onSearch"
                    placeholder="Search name, email or phone…"
                    class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-[13px] shadow-sm focus:border-purple-400 focus:outline-none focus:ring-1 focus:ring-purple-300"
                />
            </div>
            <input
                type="text"
                :value="filterPurok"
                @input="onPurokFilter"
                placeholder="Filter by purok…"
                class="w-full sm:w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-[13px] shadow-sm focus:border-purple-400 focus:outline-none focus:ring-1 focus:ring-purple-300"
            />
        </div>

        <!-- Table -->
        <div class="px-4 pb-10 pt-2 md:px-5">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div v-if="residents.data.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50">
                        <UserCircle class="h-6 w-6 text-purple-400" />
                    </div>
                    <p class="text-[14px] font-semibold text-gray-700">No residents found</p>
                    <p class="mt-1 text-[12px] text-gray-400">Try a different search term.</p>
                </div>

                <!-- Mobile list -->
                <div class="divide-y divide-gray-100 lg:hidden">
                    <div
                        v-for="r in residents.data"
                        :key="r.id"
                        class="px-4 py-3.5"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                                <span class="text-[14px] font-bold text-purple-600">{{ initials(r.name) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[14px] font-semibold text-gray-900">{{ r.name }}</p>
                                <p class="truncate text-[12px] text-gray-400">{{ r.email }}</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span v-if="r.purok" class="rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-700">
                                        {{ r.purok }}
                                    </span>
                                    <span v-if="r.sex" class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600">{{ r.sex }}</span>
                                    <span
                                        v-if="r.profile_completed_at"
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700"
                                    >Profile complete</span>
                                </div>
                            </div>
                            <span class="shrink-0 text-[11px] text-gray-300">{{ r.created_at }}</span>
                        </div>
                    </div>
                </div>

                <!-- Desktop table -->
                <table class="hidden lg:table w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Phone</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Purok</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Sex</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Profile</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in residents.data" :key="r.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50">
                                        <span class="text-[12px] font-bold text-purple-600">{{ initials(r.name) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ r.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ r.email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ r.phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="r.purok" class="rounded-full bg-purple-50 px-2.5 py-0.5 text-[11px] font-medium text-purple-700">
                                    {{ r.purok }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 capitalize">{{ r.sex ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="r.profile_completed_at"
                                    class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700">Complete</span>
                                <span v-else class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-600">Pending</span>
                            </td>
                            <td class="px-4 py-3 text-gray-400">{{ r.created_at }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="residents.last_page > 1" class="border-t border-gray-100 px-4 py-3 flex items-center justify-between">
                    <p class="text-[12px] text-gray-400">
                        Showing {{ residents.from }}–{{ residents.to }} of {{ residents.total }}
                    </p>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in residents.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            class="rounded-lg px-2.5 py-1 text-[12px] font-medium transition-colors"
                            :class="link.active
                                ? 'bg-purple-600 text-white'
                                : link.url ? 'text-gray-600 hover:bg-gray-100' : 'pointer-events-none text-gray-300'"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import { UserCircle, Search } from 'lucide-vue-next';

defineOptions({ name: 'AdminResidentsIndex', layout: AdminLayout });

interface ResidentItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    purok: string | null;
    sex: string | null;
    civil_status: string | null;
    profile_completed_at: string | null;
    created_at: string;
}

interface PaginatedResidents {
    data: ResidentItem[];
    total: number;
    from: number;
    to: number;
    last_page: number;
    links: { label: string; url: string | null; active: boolean }[];
}

const props = defineProps<{
    residents: PaginatedResidents;
    filters: { search?: string; purok?: string };
}>();

const filterSearch = ref(props.filters.search ?? '');
const filterPurok  = ref(props.filters.purok  ?? '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function onSearch(e: Event) {
    filterSearch.value = (e.target as HTMLInputElement).value;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
}

function onPurokFilter(e: Event) {
    filterPurok.value = (e.target as HTMLInputElement).value;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
}

function applyFilters() {
    router.get(admin.residents.index().url, {
        search: filterSearch.value || undefined,
        purok:  filterPurok.value  || undefined,
    }, { preserveState: true, replace: true });
}

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}
</script>
