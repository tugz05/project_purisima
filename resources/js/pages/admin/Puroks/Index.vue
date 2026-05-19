<template>
    <div class="min-h-full bg-gray-100">

        <!-- ── Hero ─────────────────────────────────────────────────────────── -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0f766e] via-[#0d9488] to-[#059669]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-emerald-300/10" />
            <div class="relative px-5 pt-6 pb-6 flex items-center justify-between">
                <div>
                    <p class="text-[12px] font-semibold uppercase tracking-widest text-teal-100/70">Admin · Management</p>
                    <h1 class="mt-1 text-[24px] font-extrabold leading-tight tracking-tight text-white">
                        Purok Management
                    </h1>
                    <p class="mt-1 text-[13px] text-teal-100/70">
                        {{ puroks.length }} purok{{ puroks.length !== 1 ? 's' : '' }} · organize barangay zones
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 rounded-2xl bg-white/20 px-5 py-3 text-[13px]
                           font-semibold text-white backdrop-blur-sm border border-white/20
                           hover:bg-white/30 active:scale-95 transition-all duration-150"
                >
                    <Plus class="h-4 w-4" />
                    Add Purok
                </button>
            </div>
        </div>

        <!-- ── Flash ─────────────────────────────────────────────────────────── -->
        <div v-if="$page.props.flash?.success"
            class="mx-4 mt-4 md:mx-5 flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3">
            <CheckCircle class="h-4 w-4 shrink-0 text-emerald-500" />
            <p class="text-[13px] text-emerald-700 font-medium">{{ $page.props.flash.success }}</p>
        </div>

        <!-- ── Empty state ───────────────────────────────────────────────────── -->
        <div v-if="puroks.length === 0"
            class="mx-4 mt-4 md:mx-5 flex flex-col items-center justify-center rounded-2xl bg-white py-20 text-center shadow-sm">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50">
                <MapPin class="h-7 w-7 text-teal-300" />
            </div>
            <p class="text-[15px] font-semibold text-gray-800">No puroks yet</p>
            <p class="mt-1 text-[13px] text-gray-400">Add the barangay's puroks to organize residents by zone.</p>
            <button @click="openCreate"
                class="mt-5 rounded-xl bg-teal-600 px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-teal-700">
                Add First Purok
            </button>
        </div>

        <!-- ── Purok cards ───────────────────────────────────────────────────── -->
        <div v-else class="px-4 pb-10 pt-4 md:px-5 md:pt-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="p in puroks"
                    :key="p.id"
                    class="group relative overflow-hidden rounded-2xl bg-white shadow-sm
                           hover:shadow-md transition-shadow duration-200"
                >
                    <!-- Active colour stripe -->
                    <div class="h-1.5 w-full transition-colors"
                        :class="p.is_active ? 'bg-gradient-to-r from-teal-400 to-emerald-400' : 'bg-gray-200'" />

                    <div class="p-5">
                        <!-- Header row -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                    :class="p.is_active ? 'bg-teal-50' : 'bg-gray-50'">
                                    <MapPin class="h-5 w-5" :class="p.is_active ? 'text-teal-500' : 'text-gray-300'" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-bold text-gray-900 leading-tight">{{ p.name }}</p>
                                    <p v-if="p.description" class="mt-0.5 truncate text-[12px] text-gray-400 leading-relaxed">
                                        {{ p.description }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                :class="p.is_active
                                    ? 'bg-teal-100 text-teal-700'
                                    : 'bg-gray-100 text-gray-400'"
                            >{{ p.is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <!-- Stats & actions row -->
                        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-1.5 text-[12px] text-gray-400">
                                <Users class="h-3.5 w-3.5" />
                                <span>
                                    <span class="font-bold text-gray-700">{{ p.resident_count }}</span>
                                    resident{{ p.resident_count !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEdit(p)"
                                    class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-[12px] font-medium
                                           text-teal-600 hover:bg-teal-50 transition-colors">
                                    <Pencil class="h-3.5 w-3.5" /> Edit
                                </button>
                                <button @click="confirmDelete(p)"
                                    class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-[12px] font-medium
                                           text-red-400 hover:bg-red-50 transition-colors">
                                    <Trash2 class="h-3.5 w-3.5" /> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════
             Create / Edit Sheet
        ════════════════════════════════════════════════════════════════════ -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full sm:max-w-md flex flex-col gap-0 p-0 overflow-hidden">

                <!-- Sheet Header -->
                <div class="border-b border-gray-100 bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                            <component :is="editTarget ? Pencil : Plus" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <SheetTitle class="text-[16px] font-bold text-white">
                                {{ editTarget ? 'Edit Purok' : 'Add New Purok' }}
                            </SheetTitle>
                            <SheetDescription class="text-[12px] text-teal-100/80 mt-0.5">
                                {{ editTarget ? 'Update this purok\'s details and status.' : 'Define a new barangay zone for residents.' }}
                            </SheetDescription>
                        </div>
                    </div>
                </div>

                <!-- Form body -->
                <div class="flex-1 overflow-y-auto">
                    <form @submit.prevent="submitForm" id="purok-form">

                        <!-- Section: Purok Details -->
                        <div class="px-6 pt-6 pb-5">
                            <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                Purok Details
                            </p>

                            <div class="space-y-1.5">
                                <Label for="name" class="text-[13px] font-semibold text-gray-700">
                                    Purok Name <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="e.g. Purok Grapes"
                                        class="h-10 rounded-xl border-gray-200 pl-9 text-[13px]
                                               focus:border-teal-400 focus:ring-teal-200"
                                    />
                                </div>
                                <p v-if="form.errors.name" class="text-[11px] text-red-500 font-medium">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="mt-4 space-y-1.5">
                                <Label for="description" class="text-[13px] font-semibold text-gray-700">
                                    Description
                                    <span class="ml-1 text-[11px] font-normal text-gray-400">(optional)</span>
                                </Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Short description of this purok's location or boundaries…"
                                    class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-gray-900
                                           placeholder:text-gray-400 resize-none focus:border-teal-400 focus:outline-none
                                           focus:ring-2 focus:ring-teal-200 transition-shadow"
                                />
                            </div>
                        </div>

                        <div class="mx-6 border-t border-dashed border-gray-200" />

                        <!-- Section: Configuration -->
                        <div class="px-6 pt-5 pb-6">
                            <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                Configuration
                            </p>

                            <!-- Active toggle -->
                            <div class="mt-5 flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3.5">
                                <div>
                                    <p class="text-[13px] font-semibold text-gray-800">Active Status</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">
                                        Inactive puroks are hidden from resident registration.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="form.is_active = !form.is_active"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200"
                                    :class="form.is_active ? 'bg-teal-500' : 'bg-gray-200'"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                        :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Sheet Footer -->
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex items-center justify-between gap-3">
                    <button type="button" @click="sheetOpen = false"
                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-[13px] font-semibold
                               text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="purok-form" :disabled="form.processing"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600
                               px-6 py-2.5 text-[13px] font-bold text-white shadow-sm
                               hover:from-teal-700 hover:to-emerald-700 disabled:opacity-60 transition-all">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : (editTarget ? 'Update Purok' : 'Create Purok') }}
                    </button>
                </div>
            </SheetContent>
        </Sheet>

        <!-- ── Delete Dialog ──────────────────────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="sm:max-w-sm rounded-2xl p-0 overflow-hidden">
                <div class="bg-red-50 px-6 py-5 border-b border-red-100">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100">
                            <AlertTriangle class="h-5 w-5 text-red-600" />
                        </div>
                        <DialogTitle class="text-[16px] font-bold text-red-900">Delete Purok</DialogTitle>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <DialogDescription class="text-[13px] text-gray-600 leading-relaxed">
                        Are you sure you want to delete
                        <span class="font-semibold text-gray-900">{{ deleteTarget?.name }}</span>?
                        This action cannot be undone. Residents assigned to this purok will not be automatically reassigned.
                    </DialogDescription>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                    <button @click="deleteDialogOpen = false"
                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-[13px] font-semibold text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="doDelete"
                        class="flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-[13px] font-bold text-white hover:bg-red-700">
                        <Trash2 class="h-4 w-4" /> Delete Purok
                    </button>
                </div>
            </DialogContent>
        </Dialog>

    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import {
    MapPin, Plus, Pencil, Trash2, Users,
    Save, Loader2, CheckCircle, AlertTriangle,
} from 'lucide-vue-next';
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({ name: 'AdminPuroksIndex', layout: AdminLayout });

interface PurokItem {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    resident_count: number;
}

const props = defineProps<{ puroks: PurokItem[] }>();

// ── Sheet ─────────────────────────────────────────────────────────────────────
const sheetOpen  = ref(false);
const editTarget = ref<PurokItem | null>(null);

const form = useForm({
    name:        '',
    description: '',
    is_active:   true,
});

function openCreate() {
    editTarget.value = null;
    form.reset();
    form.is_active  = true;
    sheetOpen.value = true;
}

function openEdit(p: PurokItem) {
    editTarget.value = p;
    form.name        = p.name;
    form.description = p.description ?? '';
    form.is_active   = p.is_active;
    sheetOpen.value  = true;
}

function submitForm() {
    if (editTarget.value) {
        form.put(admin.puroks.update(editTarget.value.id).url, {
            onSuccess: () => { sheetOpen.value = false; },
        });
    } else {
        form.post(admin.puroks.store().url, {
            onSuccess: () => { sheetOpen.value = false; form.reset(); },
        });
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteDialogOpen = ref(false);
const deleteTarget     = ref<PurokItem | null>(null);

function confirmDelete(p: PurokItem) {
    deleteTarget.value     = p;
    deleteDialogOpen.value = true;
}

function doDelete() {
    if (!deleteTarget.value) return;
    useForm({}).delete(admin.puroks.destroy(deleteTarget.value.id).url, {
        onSuccess: () => { deleteDialogOpen.value = false; },
    });
}
</script>
