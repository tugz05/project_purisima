<template>
    <StaffLayout :breadcrumbs="staffSmsBroadcastBreadcrumbs">
        <div class="min-h-full bg-gray-100">

            <!-- ── Hero ───────────────────────────────────────────────────── -->
            <div class="relative overflow-hidden bg-gradient-to-br from-[#059669] via-[#0d9488] to-[#0f766e]
                        md:mx-5 md:mt-5 md:rounded-3xl">
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
                <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-teal-300/10" />
                <div class="relative px-5 pt-6 pb-6 flex items-center justify-between">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-widest text-emerald-100/70">Staff · Communications</p>
                        <h1 class="mt-1 text-[24px] font-extrabold leading-tight tracking-tight text-white">
                            SMS Broadcast
                        </h1>
                        <p class="mt-1 text-[13px] text-emerald-100/70">
                            Send bulk SMS messages to all residents with phone numbers
                        </p>
                    </div>
                    <button
                        @click="composerOpen = true"
                        class="flex items-center gap-2 rounded-2xl bg-white/20 px-5 py-3 text-[13px]
                               font-semibold text-white backdrop-blur-sm border border-white/20
                               hover:bg-white/30 active:scale-95 transition-all duration-150"
                    >
                        <Send class="h-4 w-4" />
                        New Broadcast
                    </button>
                </div>
            </div>

            <!-- ── Flash ─────────────────────────────────────────────────── -->
            <div v-if="$page.props.flash?.success"
                class="mx-4 mt-4 md:mx-5 flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3">
                <CheckCircle class="h-4 w-4 shrink-0 text-emerald-500" />
                <p class="text-[13px] text-emerald-700 font-medium">{{ $page.props.flash.success }}</p>
            </div>

            <!-- ── Broadcast History ───────────────────────────────────── -->
            <div class="px-4 pb-10 pt-4 md:px-5 md:pt-5">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5 bg-gray-50/50">
                        <Clock class="h-4 w-4 text-gray-400" />
                        <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-500">Broadcast History</h2>
                    </div>

                    <!-- Empty -->
                    <div v-if="broadcasts.data.length === 0"
                        class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50">
                            <MessageSquare class="h-7 w-7 text-emerald-300" />
                        </div>
                        <p class="text-[15px] font-semibold text-gray-800">No broadcasts yet</p>
                        <p class="mt-1 text-[13px] text-gray-400">Send your first SMS broadcast to all residents.</p>
                        <button @click="composerOpen = true"
                            class="mt-5 rounded-xl bg-emerald-600 px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-emerald-700">
                            New Broadcast
                        </button>
                    </div>

                    <!-- Mobile list -->
                    <div v-else class="divide-y divide-gray-100 lg:hidden">
                        <div v-for="b in broadcasts.data" :key="b.id" class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[14px] font-semibold text-gray-900">{{ b.title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-[12px] text-gray-500">{{ b.message }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                    :class="statusClass(b.status)">{{ b.status }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-3 text-[11px] text-gray-400">
                                <span class="flex items-center gap-1">
                                    <Users class="h-3 w-3" /> {{ b.recipients_count }} recipients
                                </span>
                                <span class="text-emerald-500 font-medium">{{ b.sent_count }} sent</span>
                                <span v-if="b.failed_count > 0" class="text-red-400 font-medium">{{ b.failed_count }} failed</span>
                                <span class="ml-auto">{{ formatDate(b.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop table -->
                    <table v-if="broadcasts.data.length > 0" class="hidden lg:table w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Title</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Message</th>
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Recipients</th>
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Sent</th>
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Failed</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Status</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Sent by</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="b in broadcasts.data" :key="b.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-5 py-4 font-semibold text-gray-900 max-w-[160px] truncate">{{ b.title }}</td>
                                <td class="px-5 py-4 max-w-[220px]">
                                    <p class="truncate text-gray-500" :title="b.message">{{ b.message }}</p>
                                </td>
                                <td class="px-5 py-4 text-center text-gray-700 font-medium">{{ b.recipients_count }}</td>
                                <td class="px-5 py-4 text-center font-bold text-emerald-600">{{ b.sent_count }}</td>
                                <td class="px-5 py-4 text-center font-bold" :class="b.failed_count > 0 ? 'text-red-500' : 'text-gray-300'">
                                    {{ b.failed_count }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase"
                                        :class="statusClass(b.status)">{{ b.status }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-500">{{ b.creator?.name ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-400 whitespace-nowrap">{{ formatDate(b.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="broadcasts.last_page > 1"
                        class="border-t border-gray-100 px-5 py-3 flex items-center justify-between">
                        <p class="text-[12px] text-gray-400">
                            Showing {{ broadcasts.from }}–{{ broadcasts.to }} of {{ broadcasts.total }}
                        </p>
                        <div class="flex gap-2">
                            <button v-if="broadcasts.prev_page_url"
                                @click="router.visit(broadcasts.prev_page_url!)"
                                class="rounded-xl border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-600 hover:bg-gray-50">
                                ← Previous
                            </button>
                            <button v-if="broadcasts.next_page_url"
                                @click="router.visit(broadcasts.next_page_url!)"
                                class="rounded-xl border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-600 hover:bg-gray-50">
                                Next →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════
                 Compose Sheet
            ════════════════════════════════════════════════════════════ -->
            <Sheet v-model:open="composerOpen">
                <SheetContent class="w-full sm:max-w-lg flex flex-col gap-0 p-0 overflow-hidden">

                    <!-- Header -->
                    <div class="border-b border-gray-100 bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                <MessageSquare class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <SheetTitle class="text-[16px] font-bold text-white">New SMS Broadcast</SheetTitle>
                                <SheetDescription class="text-[12px] text-emerald-100/80 mt-0.5">
                                    Sent to every resident who has a phone number on file.
                                </SheetDescription>
                            </div>
                        </div>
                    </div>

                    <!-- Form body -->
                    <div class="flex-1 overflow-y-auto">
                        <form @submit.prevent="submit" id="sms-form">

                            <!-- Section: Message Details -->
                            <div class="px-6 pt-6 pb-5">
                                <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                    Message Details
                                </p>

                                <div class="space-y-1.5">
                                    <Label for="title" class="text-[13px] font-semibold text-gray-700">
                                        Title / Subject <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="e.g. Barangay Health Alert"
                                        class="h-10 rounded-xl border-gray-200 text-[13px] focus:border-emerald-400 focus:ring-emerald-200"
                                        :class="{ 'border-red-400': form.errors.title }"
                                    />
                                    <p v-if="form.errors.title" class="text-[11px] text-red-500 font-medium">
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <div class="mt-4 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <Label for="message" class="text-[13px] font-semibold text-gray-700">
                                            Message <span class="text-red-500">*</span>
                                        </Label>
                                        <span class="text-[11px] font-medium"
                                            :class="form.message.length > 1440 ? 'text-red-500' : 'text-gray-400'">
                                            {{ form.message.length }} / 1600
                                        </span>
                                    </div>
                                    <textarea
                                        id="message"
                                        v-model="form.message"
                                        rows="7"
                                        maxlength="1600"
                                        placeholder="Type your broadcast message here…"
                                        class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-gray-900
                                               placeholder:text-gray-400 resize-none focus:border-emerald-400 focus:outline-none
                                               focus:ring-2 focus:ring-emerald-200 transition-shadow"
                                        :class="{ 'border-red-400': form.errors.message }"
                                    />
                                    <p v-if="form.errors.message" class="text-[11px] text-red-500 font-medium">
                                        {{ form.errors.message }}
                                    </p>
                                </div>
                            </div>

                            <div class="mx-6 border-t border-dashed border-gray-200" />

                            <!-- Info note -->
                            <div class="mx-6 my-5 rounded-xl bg-amber-50 border border-amber-100 px-4 py-3.5">
                                <div class="flex gap-2.5">
                                    <Info class="h-4 w-4 shrink-0 text-amber-500 mt-0.5" />
                                    <div>
                                        <p class="text-[12px] font-semibold text-amber-800">Before sending</p>
                                        <p class="mt-0.5 text-[11px] text-amber-700 leading-relaxed">
                                            Standard SMS is 160 characters. Longer messages are split automatically by the carrier.
                                            Messages are queued and sent asynchronously — delivery may take a few minutes.
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex items-center justify-between gap-3">
                        <button type="button" @click="composerOpen = false"
                            class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-[13px] font-semibold
                                   text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" form="sms-form" :disabled="form.processing"
                            class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600
                                   px-6 py-2.5 text-[13px] font-bold text-white shadow-sm
                                   hover:from-emerald-700 hover:to-teal-700 disabled:opacity-60 transition-all">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            {{ form.processing ? 'Sending…' : 'Send Broadcast' }}
                        </button>
                    </div>
                </SheetContent>
            </Sheet>

        </div>
    </StaffLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { MessageSquare, Send, Clock, CheckCircle, Users, Info, Loader2 } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import staff from '@/routes/staff';

interface Creator { id: number; name: string; }
interface Broadcast {
    id: number; title: string; message: string;
    recipients_count: number; sent_count: number; failed_count: number;
    status: string; created_at: string; creator: Creator | null;
}
interface PaginatedBroadcasts {
    data: Broadcast[]; total: number; from: number; to: number;
    last_page: number; prev_page_url: string | null; next_page_url: string | null;
}

defineProps<{ broadcasts: PaginatedBroadcasts }>();

const { staffSmsBroadcastBreadcrumbs } = useBreadcrumbs();
const composerOpen = ref(false);

const form = useForm({ title: '', message: '' });

function submit() {
    form.post(staff.sms.store().url, {
        onSuccess: () => { composerOpen.value = false; form.reset(); },
    });
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        processing: 'bg-amber-100 text-amber-700',
        completed:  'bg-emerald-100 text-emerald-700',
        failed:     'bg-red-100 text-red-700',
        pending:    'bg-gray-100 text-gray-600',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>
