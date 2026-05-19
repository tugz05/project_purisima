<template>
    <div class="min-h-full bg-gray-100">

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0f766e] via-[#0d9488] to-[#059669]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="relative px-5 pt-6 pb-6 flex items-end justify-between">
                <div>
                    <p class="text-[13px] font-medium text-teal-100/80">Admin</p>
                    <h1 class="mt-0.5 text-[22px] font-extrabold leading-tight tracking-tight text-white">
                        Purok Management
                    </h1>
                    <p class="mt-1 text-[13px] text-teal-100/70">{{ puroks.length }} purok{{ puroks.length !== 1 ? 's' : '' }}</p>
                </div>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2.5 text-[13px]
                           font-semibold text-white backdrop-blur-sm hover:bg-white/30 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Add Purok
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mx-4 mt-4 md:mx-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-[13px] text-emerald-700 font-medium">
            {{ $page.props.flash.success }}
        </div>

        <!-- Cards grid -->
        <div class="px-4 pb-10 pt-4 md:px-5 md:pt-5">
            <div v-if="puroks.length === 0" class="rounded-2xl bg-white shadow-sm flex flex-col items-center justify-center py-16 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50">
                    <MapPin class="h-6 w-6 text-teal-400" />
                </div>
                <p class="text-[14px] font-semibold text-gray-700">No puroks yet</p>
                <p class="mt-1 text-[12px] text-gray-400">Add the barangay's puroks to get started.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="p in puroks"
                    :key="p.id"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm"
                >
                    <!-- Color bar -->
                    <div class="h-1.5 w-full" :class="p.is_active ? 'bg-teal-400' : 'bg-gray-200'" />

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-bold text-gray-900">{{ p.name }}</p>
                                <p v-if="p.description" class="mt-0.5 truncate text-[12px] text-gray-400">{{ p.description }}</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                :class="p.is_active ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500'"
                            >{{ p.is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-[12px] text-gray-400">
                                <span class="font-semibold text-gray-700">{{ p.resident_count }}</span> resident{{ p.resident_count !== 1 ? 's' : '' }}
                            </span>
                            <div class="flex gap-1">
                                <button @click="openEdit(p)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-teal-600">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button @click="confirmDelete(p)" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Sheet -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>{{ editTarget ? 'Edit Purok' : 'Add Purok' }}</SheetTitle>
                    <SheetDescription>{{ editTarget ? 'Update purok details.' : 'Create a new purok for the barangay.' }}</SheetDescription>
                </SheetHeader>

                <form @submit.prevent="submitForm" class="mt-6 space-y-4">
                    <div class="space-y-1.5">
                        <Label for="name">Purok Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. Purok 1" />
                        <p v-if="form.errors.name" class="text-[12px] text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="description">Description (optional)</Label>
                        <Input id="description" v-model="form.description" placeholder="Short description" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="sort_order">Sort Order</Label>
                        <Input id="sort_order" type="number" v-model.number="form.sort_order" min="0" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-gray-300 text-teal-600" />
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="sheetOpen = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-xl bg-teal-600 px-5 py-2 text-[13px] font-semibold text-white hover:bg-teal-700 disabled:opacity-60">
                            {{ form.processing ? 'Saving…' : (editTarget ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Delete Purok</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong>? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <button @click="deleteDialogOpen = false"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="doDelete"
                        class="rounded-xl bg-red-600 px-5 py-2 text-[13px] font-semibold text-white hover:bg-red-700">
                        Delete
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import { MapPin, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
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

const sheetOpen  = ref(false);
const editTarget = ref<PurokItem | null>(null);

const form = useForm({
    name:        '',
    description: '',
    is_active:   true,
    sort_order:  0,
});

function openCreate() {
    editTarget.value = null;
    form.reset();
    form.is_active  = true;
    form.sort_order = props.puroks.length + 1;
    sheetOpen.value = true;
}

function openEdit(p: PurokItem) {
    editTarget.value = p;
    form.name        = p.name;
    form.description = p.description ?? '';
    form.is_active   = p.is_active;
    form.sort_order  = p.sort_order;
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

const deleteDialogOpen = ref(false);
const deleteTarget     = ref<PurokItem | null>(null);

function confirmDelete(p: PurokItem) {
    deleteTarget.value = p;
    deleteDialogOpen.value = true;
}

function doDelete() {
    if (!deleteTarget.value) return;
    useForm({}).delete(admin.puroks.destroy(deleteTarget.value.id).url, {
        onSuccess: () => { deleteDialogOpen.value = false; },
    });
}
</script>
