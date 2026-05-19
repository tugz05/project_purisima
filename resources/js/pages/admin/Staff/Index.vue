<template>
    <div class="min-h-full bg-gray-100">

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#1a56db] via-[#1e40af] to-[#7c3aed]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="relative px-5 pt-6 pb-6 flex items-end justify-between">
                <div>
                    <p class="text-[13px] font-medium text-blue-200/90">Admin</p>
                    <h1 class="mt-0.5 text-[22px] font-extrabold leading-tight tracking-tight text-white">
                        Staff Management
                    </h1>
                    <p class="mt-1 text-[13px] text-blue-200/70">{{ staff.length }} staff account{{ staff.length !== 1 ? 's' : '' }}</p>
                </div>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2.5 text-[13px]
                           font-semibold text-white backdrop-blur-sm hover:bg-white/30 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Add Staff
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mx-4 mt-4 md:mx-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-[13px] text-emerald-700 font-medium">
            {{ $page.props.flash.success }}
        </div>

        <!-- Table -->
        <div class="px-4 pb-10 pt-4 md:px-5 md:pt-5">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div v-if="staff.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50">
                        <Users class="h-6 w-6 text-blue-400" />
                    </div>
                    <p class="text-[14px] font-semibold text-gray-700">No staff accounts</p>
                    <p class="mt-1 text-[12px] text-gray-400">Add your first staff member to get started.</p>
                </div>

                <!-- Mobile list -->
                <div class="divide-y divide-gray-100 lg:hidden">
                    <div
                        v-for="s in staff"
                        :key="s.id"
                        class="flex items-center gap-3 px-4 py-3.5"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                            <span class="text-[14px] font-bold text-blue-600">{{ initials(s.name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-gray-900">{{ s.name }}</p>
                            <p class="truncate text-[12px] text-gray-400">{{ s.email }}</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="dt in s.document_types"
                                    :key="dt.id"
                                    class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700"
                                >{{ dt.name }}</span>
                                <span v-if="s.document_types.length === 0"
                                    class="text-[11px] text-gray-300 italic">All types</span>
                            </div>
                        </div>
                        <div class="shrink-0 flex gap-1">
                            <button @click="openEdit(s)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-blue-600">
                                <Pencil class="h-4 w-4" />
                            </button>
                            <button @click="confirmDelete(s)" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                <Trash2 class="h-4 w-4" />
                            </button>
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
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Assigned Types</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-500">Joined</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in staff" :key="s.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50">
                                        <span class="text-[12px] font-bold text-blue-600">{{ initials(s.name) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ s.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ s.email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="dt in s.document_types"
                                        :key="dt.id"
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700"
                                    >{{ dt.name }}</span>
                                    <span v-if="s.document_types.length === 0" class="text-gray-300 italic text-[12px]">All types</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-400">{{ s.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEdit(s)" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-blue-600">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button @click="confirmDelete(s)" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Sheet -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full sm:max-w-lg overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>{{ editTarget ? 'Edit Staff' : 'Add Staff' }}</SheetTitle>
                    <SheetDescription>
                        {{ editTarget ? 'Update staff account details and document type assignments.' : 'Create a new staff account.' }}
                    </SheetDescription>
                </SheetHeader>

                <form @submit.prevent="submitForm" class="mt-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="first_name">First Name</Label>
                            <Input id="first_name" v-model="form.first_name" placeholder="Juan" />
                            <p v-if="form.errors.first_name" class="text-[12px] text-red-500">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="last_name">Last Name</Label>
                            <Input id="last_name" v-model="form.last_name" placeholder="Dela Cruz" />
                            <p v-if="form.errors.last_name" class="text-[12px] text-red-500">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="form.email" placeholder="staff@example.com" />
                        <p v-if="form.errors.email" class="text-[12px] text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="phone">Phone (optional)</Label>
                        <Input id="phone" v-model="form.phone" placeholder="09XXXXXXXXX" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password">{{ editTarget ? 'New Password (leave blank to keep)' : 'Password' }}</Label>
                        <Input id="password" type="password" v-model="form.password" />
                        <p v-if="form.errors.password" class="text-[12px] text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                    </div>

                    <!-- Document Types -->
                    <div class="space-y-2">
                        <Label>Assigned Document Types</Label>
                        <p class="text-[12px] text-gray-400">Leave empty to allow all types.</p>
                        <div class="max-h-48 overflow-y-auto space-y-1.5 rounded-xl border border-gray-200 p-3">
                            <label
                                v-for="dt in documentTypes"
                                :key="dt.id"
                                class="flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="dt.id"
                                    v-model="form.document_type_ids"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600"
                                />
                                <span class="text-[13px] text-gray-700">{{ dt.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="sheetOpen = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-xl bg-blue-600 px-5 py-2 text-[13px] font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
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
                    <DialogTitle>Remove Staff Account</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove <strong>{{ deleteTarget?.name }}</strong>? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <button @click="deleteDialogOpen = false"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="doDelete"
                        class="rounded-xl bg-red-600 px-5 py-2 text-[13px] font-semibold text-white hover:bg-red-700">
                        Remove
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
import { Users, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({ name: 'AdminStaffIndex', layout: AdminLayout });

interface DocType { id: number; name: string; }
interface StaffItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    created_at: string;
    document_types: DocType[];
}

const props = defineProps<{
    staff: StaffItem[];
    documentTypes: DocType[];
}>();

// ── Sheet (create / edit) ─────────────────────────────────────────────────────
const sheetOpen  = ref(false);
const editTarget = ref<StaffItem | null>(null);

const form = useForm({
    first_name:           '',
    last_name:            '',
    email:                '',
    phone:                '',
    password:             '',
    password_confirmation: '',
    document_type_ids:    [] as number[],
});

function openCreate() {
    editTarget.value = null;
    form.reset();
    form.document_type_ids = [];
    sheetOpen.value = true;
}

function openEdit(s: StaffItem) {
    editTarget.value = s;
    const parts = s.name.split(' ');
    form.first_name = parts[0] ?? '';
    form.last_name  = parts.slice(1).join(' ');
    form.email      = s.email;
    form.phone      = s.phone ?? '';
    form.password   = '';
    form.password_confirmation = '';
    form.document_type_ids = s.document_types.map(d => d.id);
    sheetOpen.value = true;
}

function submitForm() {
    if (editTarget.value) {
        form.put(admin.staff.update(editTarget.value.id).url, {
            onSuccess: () => { sheetOpen.value = false; },
        });
    } else {
        form.post(admin.staff.store().url, {
            onSuccess: () => { sheetOpen.value = false; form.reset(); },
        });
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteDialogOpen = ref(false);
const deleteTarget     = ref<StaffItem | null>(null);

function confirmDelete(s: StaffItem) {
    deleteTarget.value = s;
    deleteDialogOpen.value = true;
}

function doDelete() {
    if (!deleteTarget.value) return;
    useForm({}).delete(admin.staff.destroy(deleteTarget.value.id).url, {
        onSuccess: () => { deleteDialogOpen.value = false; },
    });
}

function initials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}
</script>
