<template>
    <div class="min-h-full bg-gray-100">

        <!-- ═══════════════════════════════════════════════════════════════════
             HERO  —  gradient banner that contains stats at the bottom
             Full-bleed on mobile / contained rounded card on md+
        ════════════════════════════════════════════════════════════════════ -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-[#1a56db] via-[#1e40af] to-[#0d9488]
                   md:mx-5 md:mt-5 md:rounded-3xl"
        >
            <!-- Background shapes -->
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="pointer-events-none absolute -bottom-10 left-1/2 h-56 w-56 -translate-x-1/2 rounded-full bg-teal-400/15" />
            <div class="pointer-events-none absolute right-0 top-1/2 h-32 w-32 -translate-y-1/2 rounded-full bg-blue-300/10" />

            <!-- Profile -->
            <div class="relative px-5 pt-6 pb-5">
                <div class="flex items-center gap-4">
                    <!-- Avatar -->
                    <div class="shrink-0">
                        <img
                            v-if="user.photo_url"
                            :src="user.photo_url"
                            alt=""
                            class="h-[60px] w-[60px] rounded-2xl object-cover shadow-lg ring-2 ring-white/40"
                        />
                        <div
                            v-else
                            class="flex h-[60px] w-[60px] items-center justify-center rounded-2xl
                                   bg-white/20 shadow-lg ring-2 ring-white/40 backdrop-blur-sm"
                        >
                            <span class="text-xl font-bold text-white select-none">{{ userInitials }}</span>
                        </div>
                    </div>

                    <!-- Name / location -->
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-medium text-blue-200/90">Good {{ timeOfDay }},</p>
                        <h1 class="mt-0.5 truncate text-[22px] font-extrabold leading-tight tracking-tight text-white">
                            {{ displayName }}
                        </h1>
                        <p class="mt-1 flex items-center gap-1 text-[13px] text-blue-200/80">
                            <MapPin class="h-3.5 w-3.5 shrink-0 text-teal-300" />
                            <span class="truncate">{{ user.purok ?? 'Barangay Purisima' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Chips -->
                <div class="mt-3.5 flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/15
                               px-3 py-1 text-[12px] font-medium text-white backdrop-blur-sm"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-300" />
                        Registered resident
                    </span>
                    <span
                        v-if="memberSince"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/10
                               px-3 py-1 text-[12px] text-blue-100/90 backdrop-blur-sm"
                    >
                        <CalendarDays class="h-3.5 w-3.5 text-blue-300" />
                        Since {{ memberSince }}
                    </span>
                </div>
            </div>

            <!-- ── Stats row inside hero (frosted glass) ── -->
            <div class="relative mx-4 mb-5 overflow-hidden rounded-2xl border border-white/10 bg-white/10 backdrop-blur-sm">
                <div class="grid grid-cols-4 divide-x divide-white/10">
                    <div
                        v-for="s in statTiles"
                        :key="s.label"
                        class="flex flex-col items-center gap-0.5 py-3.5"
                    >
                        <span class="text-[24px] font-extrabold leading-none text-white">
                            {{ s.value }}
                        </span>
                        <span class="text-[11px] font-medium text-white/60">{{ s.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════
             CONTENT  —  white cards on gray background
        ════════════════════════════════════════════════════════════════════ -->
        <div class="px-4 pb-28 pt-4 space-y-4 md:px-5 md:pb-10 md:pt-5 lg:grid lg:grid-cols-3 lg:gap-5 lg:space-y-0">

            <!-- ── Recent Transactions ── -->
            <div class="lg:col-span-2">
                <!-- Section header -->
                <div class="mb-3 flex items-center justify-between px-0.5">
                    <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-400">
                        Recent Transactions
                    </h2>
                    <Link
                        :href="transactionsUrl"
                        class="text-[13px] font-semibold text-blue-600"
                    >
                        View all →
                    </Link>
                </div>

                <!-- Empty state -->
                <div
                    v-if="recentTransactions.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl
                           bg-white px-6 py-12 text-center shadow-sm"
                >
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50">
                        <FileText class="h-6 w-6 text-blue-400" />
                    </div>
                    <p class="text-[14px] font-semibold text-gray-700">No transactions yet</p>
                    <p class="mt-1 text-[12px] text-gray-400">Your document requests will appear here.</p>
                </div>

                <!-- Transaction list -->
                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <Link
                        v-for="tx in recentTransactions"
                        :key="tx.id"
                        :href="`/resident/transactions/${tx.id}`"
                        class="flex items-center gap-3.5 border-b border-gray-100 px-4 py-3.5
                               last:border-b-0 active:bg-gray-50/80"
                    >
                        <!-- Colored status pill on left -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="txIconBg(tx.status)"
                        >
                            <component :is="txIcon(tx.status)" class="h-5 w-5" :class="txIconColor(tx.status)" />
                        </div>

                        <!-- Text -->
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-gray-900">
                                {{ tx.document_type_name ?? tx.title }}
                            </p>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="txBadge(tx.status)"
                                >{{ txLabel(tx.status) }}</span>
                                <span class="text-[11px] text-gray-400 font-mono">
                                    #{{ tx.transaction_id ?? tx.id }}
                                </span>
                            </div>
                        </div>

                        <!-- Date + chevron -->
                        <div class="shrink-0 flex flex-col items-end gap-1.5">
                            <span class="text-[11px] text-gray-400 whitespace-nowrap">
                                {{ formatDate(tx.created_at) }}
                            </span>
                            <ChevronRight class="h-4 w-4 text-gray-300" />
                        </div>
                    </Link>
                </div>
            </div>

            <!-- ── Announcements ── -->
            <div v-if="announcements.length" class="lg:col-span-1">
                <div class="mb-3 px-0.5">
                    <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-400">
                        Announcements
                    </h2>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="a in announcements"
                        :key="a.id"
                        class="overflow-hidden rounded-2xl bg-white shadow-sm"
                    >
                        <!-- Colored top strip by type -->
                        <div class="h-1 w-full" :class="aTopBar(a.type)" />

                        <div class="p-4">
                            <!-- Badge row -->
                            <div class="mb-2.5 flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                    :class="aIconBg(a.type)"
                                >
                                    <component :is="aIcon(a.type)" class="h-3.5 w-3.5" :class="aIconColor(a.type)" />
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="aBadge(a.type)"
                                >{{ a.type }}</span>
                                <span
                                    v-if="a.is_featured"
                                    class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px]
                                           font-bold uppercase tracking-wide text-amber-700"
                                >Featured</span>
                            </div>

                            <p class="text-[14px] font-semibold leading-snug text-gray-900">
                                {{ a.title }}
                            </p>
                            <p class="mt-1 line-clamp-2 text-[12px] leading-relaxed text-gray-500">
                                {{ a.excerpt }}
                            </p>
                            <p class="mt-2.5 text-[11px] text-gray-400">
                                {{ formatDate(a.published_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useAuth } from '@/composables/useAuth';
import resident from '@/routes/resident';
import {
    MapPin, CheckCircle2, CalendarDays, ChevronRight,
    FileText, Clock, CheckCircle, XCircle, AlertCircle,
    Megaphone, AlertTriangle, Info, Calendar, FileCheck,
} from 'lucide-vue-next';

defineOptions({ name: 'ResidentDashboard', layout: ResidentLayout });

// ── Props ─────────────────────────────────────────────────────────────────────
interface TxItem {
    id: number;
    transaction_id: string | null;
    title: string;
    status: string;
    payment_status: string | null;
    document_type_name: string | null;
    created_at: string | null;
}
interface AItem {
    id: number;
    title: string;
    type: string;
    priority: string;
    is_featured: boolean;
    published_at: string | null;
    excerpt: string;
}
interface Stats {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    rejected: number;
}

const props = defineProps<{
    stats: Stats;
    recentTransactions: TxItem[];
    announcements: AItem[];
    memberSince: string | null;
}>();

// ── Auth + time greeting ──────────────────────────────────────────────────────
const { user, displayName, userInitials } = useAuth();

const timeOfDay = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'morning';
    if (h < 17) return 'afternoon';
    return 'evening';
});

const transactionsUrl = resident.transactions.index().url;

// ── Stat tiles (inside hero) ──────────────────────────────────────────────────
const statTiles = computed(() => [
    { label: 'Total',    value: props.stats.total       },
    { label: 'Pending',  value: props.stats.pending     },
    { label: 'Active',   value: props.stats.in_progress },
    { label: 'Done',     value: props.stats.completed   },
]);

// ── Transaction lookup table ──────────────────────────────────────────────────
const TX = {
    pending:     { icon: Clock,        bg: 'bg-amber-50',   color: 'text-amber-500',   badge: 'bg-amber-100 text-amber-700',     label: 'Pending'    },
    in_progress: { icon: FileCheck,    bg: 'bg-blue-50',    color: 'text-blue-500',    badge: 'bg-blue-100 text-blue-700',       label: 'Processing' },
    completed:   { icon: CheckCircle,  bg: 'bg-emerald-50', color: 'text-emerald-500', badge: 'bg-emerald-100 text-emerald-700', label: 'Completed'  },
    rejected:    { icon: XCircle,      bg: 'bg-red-50',     color: 'text-red-400',     badge: 'bg-red-100 text-red-600',         label: 'Rejected'   },
    cancelled:   { icon: XCircle,      bg: 'bg-red-50',     color: 'text-red-400',     badge: 'bg-red-100 text-red-600',         label: 'Cancelled'  },
} as const;

type TxKey = keyof typeof TX;
const txFallback = { icon: AlertCircle, bg: 'bg-gray-50', color: 'text-gray-400', badge: 'bg-gray-100 text-gray-600', label: 'Unknown' };

function txIcon(s: string)      { return (TX[s as TxKey] ?? txFallback).icon; }
function txIconBg(s: string)    { return (TX[s as TxKey] ?? txFallback).bg; }
function txIconColor(s: string) { return (TX[s as TxKey] ?? txFallback).color; }
function txBadge(s: string)     { return (TX[s as TxKey] ?? txFallback).badge; }
function txLabel(s: string)     { return (TX[s as TxKey] ?? txFallback).label; }

// ── Announcement lookup table ─────────────────────────────────────────────────
const AT = {
    urgent:  { icon: AlertTriangle, bg: 'bg-red-50',    color: 'text-red-500',    badge: 'bg-red-100 text-red-700',       bar: 'bg-red-400'    },
    event:   { icon: Calendar,      bg: 'bg-purple-50', color: 'text-purple-500', badge: 'bg-purple-100 text-purple-700', bar: 'bg-purple-400' },
    notice:  { icon: Info,          bg: 'bg-sky-50',    color: 'text-sky-500',    badge: 'bg-sky-100 text-sky-700',       bar: 'bg-sky-400'    },
    general: { icon: Megaphone,     bg: 'bg-blue-50',   color: 'text-blue-500',   badge: 'bg-blue-100 text-blue-700',     bar: 'bg-blue-400'   },
} as const;

type ATKey = keyof typeof AT;
const atFallback = { icon: Megaphone, bg: 'bg-blue-50', color: 'text-blue-500', badge: 'bg-blue-100 text-blue-700', bar: 'bg-blue-400' };

function aIcon(t: string)       { return (AT[t as ATKey] ?? atFallback).icon; }
function aIconBg(t: string)     { return (AT[t as ATKey] ?? atFallback).bg; }
function aIconColor(t: string)  { return (AT[t as ATKey] ?? atFallback).color; }
function aBadge(t: string)      { return (AT[t as ATKey] ?? atFallback).badge; }
function aTopBar(t: string)     { return (AT[t as ATKey] ?? atFallback).bar; }

// ── Relative date ─────────────────────────────────────────────────────────────
function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d   = new Date(iso);
    const sec = (Date.now() - d.getTime()) / 1000;
    if (sec < 60)     return 'Just now';
    if (sec < 3600)   return `${Math.floor(sec / 60)}m ago`;
    if (sec < 86400)  return `${Math.floor(sec / 3600)}h ago`;
    if (sec < 604800) return `${Math.floor(sec / 86400)}d ago`;
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}
</script>
