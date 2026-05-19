<template>
    <div class="min-h-full bg-gray-100">

        <!-- ── Hero ─────────────────────────────────────────────────────────── -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#1a56db] via-[#1e40af] to-[#7c3aed]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" />
            <div class="pointer-events-none absolute bottom-0 left-1/4 h-40 w-40 rounded-full bg-purple-400/10" />
            <div class="relative px-5 pt-6 pb-6 flex items-center justify-between">
                <div>
                    <p class="text-[12px] font-semibold uppercase tracking-widest text-blue-200/70">Admin · Management</p>
                    <h1 class="mt-1 text-[24px] font-extrabold leading-tight tracking-tight text-white">
                        Staff Accounts
                    </h1>
                    <p class="mt-1 text-[13px] text-blue-200/70">
                        {{ staff.length }} member{{ staff.length !== 1 ? 's' : '' }} · manage access &amp; assignments
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 rounded-2xl bg-white/20 px-5 py-3 text-[13px]
                           font-semibold text-white backdrop-blur-sm border border-white/20
                           hover:bg-white/30 active:scale-95 transition-all duration-150"
                >
                    <UserPlus class="h-4 w-4" />
                    Add Staff
                </button>
            </div>
        </div>

        <!-- ── Flash ─────────────────────────────────────────────────────────── -->
        <div v-if="$page.props.flash?.success"
            class="mx-4 mt-4 md:mx-5 flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3">
            <CheckCircle class="h-4 w-4 shrink-0 text-emerald-500" />
            <p class="text-[13px] text-emerald-700 font-medium">{{ $page.props.flash.success }}</p>
        </div>

        <!-- ── Content ───────────────────────────────────────────────────────── -->
        <div class="px-4 pb-10 pt-4 md:px-5 md:pt-5">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">

                <!-- Empty -->
                <div v-if="staff.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50">
                        <Users class="h-7 w-7 text-blue-300" />
                    </div>
                    <p class="text-[15px] font-semibold text-gray-800">No staff accounts yet</p>
                    <p class="mt-1 text-[13px] text-gray-400">Add your first staff member to get started.</p>
                    <button @click="openCreate"
                        class="mt-5 rounded-xl bg-blue-600 px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-blue-700">
                        Add Staff Member
                    </button>
                </div>

                <!-- Mobile list -->
                <div v-else class="divide-y divide-gray-100 lg:hidden">
                    <div v-for="s in staff" :key="s.id" class="flex items-start gap-3.5 px-4 py-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-sm">
                            <span class="text-[13px] font-bold text-white">{{ initials(s.name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-gray-900">{{ s.name }}</p>
                            <p class="truncate text-[12px] text-gray-400">{{ s.email }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <span v-for="dt in s.document_types" :key="dt.id"
                                    class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-100">
                                    {{ dt.name }}
                                </span>
                                <span v-if="s.document_types.length === 0"
                                    class="text-[11px] text-gray-300 italic">All document types</span>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-1 pt-0.5">
                            <button @click="openEdit(s)"
                                class="rounded-xl p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button @click="confirmDelete(s)"
                                class="rounded-xl p-2 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop table -->
                <table v-if="staff.length > 0" class="hidden lg:table w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Staff Member</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Contact</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Assigned Document Types</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Joined</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="s in staff" :key="s.id" class="group hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl
                                                bg-gradient-to-br from-blue-500 to-indigo-600 shadow-sm">
                                        <span class="text-[12px] font-bold text-white">{{ initials(s.name) }}</span>
                                    </div>
                                    <span class="font-semibold text-gray-900">{{ s.name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-gray-700">{{ s.email }}</p>
                                <p v-if="s.phone" class="text-[12px] text-gray-400 mt-0.5">{{ s.phone }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="dt in s.document_types" :key="dt.id"
                                        class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 ring-1 ring-blue-100">
                                        {{ dt.name }}
                                    </span>
                                    <span v-if="s.document_types.length === 0"
                                        class="text-[12px] text-gray-300 italic">All types</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-400">{{ s.created_at }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEdit(s)"
                                        class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-[12px] font-medium
                                               text-blue-600 hover:bg-blue-50 transition-colors">
                                        <Pencil class="h-3.5 w-3.5" /> Edit
                                    </button>
                                    <button @click="confirmDelete(s)"
                                        class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-[12px] font-medium
                                               text-red-500 hover:bg-red-50 transition-colors">
                                        <Trash2 class="h-3.5 w-3.5" /> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════
             Create / Edit Sheet
        ════════════════════════════════════════════════════════════════════ -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full sm:max-w-xl flex flex-col gap-0 p-0 overflow-hidden">

                <!-- Sheet Header -->
                <div class="border-b border-gray-100 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                            <component :is="editTarget ? Pencil : UserPlus" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <SheetTitle class="text-[16px] font-bold text-white">
                                {{ editTarget ? 'Edit Staff Account' : 'Add New Staff' }}
                            </SheetTitle>
                            <SheetDescription class="text-[12px] text-blue-100/80 mt-0.5">
                                {{ editTarget ? 'Update details and document type assignments.' : 'Create a staff account and assign document types.' }}
                            </SheetDescription>
                        </div>
                    </div>
                </div>

                <!-- Form body (scrollable) -->
                <div class="flex-1 overflow-y-auto">
                    <form @submit.prevent="submitForm" id="staff-form">

                        <!-- Section: Personal Info -->
                        <div class="px-6 pt-6 pb-5">
                            <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                Personal Information
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="first_name" class="text-[13px] font-semibold text-gray-700">
                                        First Name <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="first_name"
                                        v-model="form.first_name"
                                        placeholder="e.g. Juan"
                                        class="h-10 rounded-xl border-gray-200 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                    />
                                    <p v-if="form.errors.first_name" class="text-[11px] text-red-500 font-medium">
                                        {{ form.errors.first_name }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="last_name" class="text-[13px] font-semibold text-gray-700">
                                        Last Name <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="last_name"
                                        v-model="form.last_name"
                                        placeholder="e.g. Dela Cruz"
                                        class="h-10 rounded-xl border-gray-200 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                    />
                                    <p v-if="form.errors.last_name" class="text-[11px] text-red-500 font-medium">
                                        {{ form.errors.last_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-1.5">
                                <Label for="email" class="text-[13px] font-semibold text-gray-700">
                                    Email Address <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        placeholder="staff@example.com"
                                        class="h-10 rounded-xl border-gray-200 pl-9 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-[11px] text-red-500 font-medium">{{ form.errors.email }}</p>
                            </div>

                            <div class="mt-4 space-y-1.5">
                                <Label for="phone" class="text-[13px] font-semibold text-gray-700">Phone Number</Label>
                                <div class="relative">
                                    <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        placeholder="09XXXXXXXXX (optional)"
                                        class="h-10 rounded-xl border-gray-200 pl-9 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mx-6 border-t border-dashed border-gray-200" />

                        <!-- Section: Password -->
                        <div class="px-6 pt-5 pb-5">
                            <p class="mb-4 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                {{ editTarget ? 'Change Password' : 'Set Password' }}
                            </p>
                            <p v-if="editTarget" class="mb-3 text-[12px] text-gray-400 -mt-1">
                                Leave blank to keep the current password.
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="password" class="text-[13px] font-semibold text-gray-700">
                                        Password <span v-if="!editTarget" class="text-red-500">*</span>
                                    </Label>
                                    <div class="relative">
                                        <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="password"
                                            type="password"
                                            v-model="form.password"
                                            placeholder="Min. 8 characters"
                                            class="h-10 rounded-xl border-gray-200 pl-9 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                        />
                                    </div>
                                    <p v-if="form.errors.password" class="text-[11px] text-red-500 font-medium">{{ form.errors.password }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="password_confirmation" class="text-[13px] font-semibold text-gray-700">
                                        Confirm <span v-if="!editTarget" class="text-red-500">*</span>
                                    </Label>
                                    <div class="relative">
                                        <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            v-model="form.password_confirmation"
                                            placeholder="Repeat password"
                                            class="h-10 rounded-xl border-gray-200 pl-9 text-[13px] focus:border-blue-400 focus:ring-blue-200"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mx-6 border-t border-dashed border-gray-200" />

                        <!-- Section: Document Type Assignments -->
                        <div class="px-6 pt-5 pb-6">
                            <div class="mb-4">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                    Document Type Assignments
                                </p>
                                <p class="mt-1 text-[12px] text-gray-400 leading-relaxed">
                                    Select which document types this staff can process.
                                    Leave all unchecked to allow access to all types.
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="max-h-52 overflow-y-auto divide-y divide-gray-100">
                                    <label
                                        v-for="dt in documentTypes"
                                        :key="dt.id"
                                        class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-blue-50/50 transition-colors"
                                        :class="{ 'bg-blue-50/70': form.document_type_ids.includes(dt.id) }"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="dt.id"
                                            v-model="form.document_type_ids"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 accent-blue-600"
                                        />
                                        <span class="text-[13px] text-gray-700 font-medium">{{ dt.name }}</span>
                                        <span v-if="form.document_type_ids.includes(dt.id)"
                                            class="ml-auto text-[10px] font-bold text-blue-600 uppercase tracking-wide">
                                            Assigned
                                        </span>
                                    </label>
                                </div>
                                <div class="border-t border-gray-100 bg-gray-50 px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-[11px] text-gray-400">
                                        {{ form.document_type_ids.length > 0
                                            ? `${form.document_type_ids.length} of ${documentTypes.length} selected`
                                            : 'No restriction — all types allowed' }}
                                    </span>
                                    <button v-if="form.document_type_ids.length > 0"
                                        type="button"
                                        @click="form.document_type_ids = []"
                                        class="text-[11px] text-red-400 hover:text-red-600 font-medium">
                                        Clear all
                                    </button>
                                </div>
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
                    <button type="submit" form="staff-form" :disabled="form.processing"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600
                               px-6 py-2.5 text-[13px] font-bold text-white shadow-sm
                               hover:from-blue-700 hover:to-indigo-700 disabled:opacity-60 transition-all">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : (editTarget ? 'Update Staff' : 'Create Staff') }}
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
                        <DialogTitle class="text-[16px] font-bold text-red-900">Remove Staff Account</DialogTitle>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <DialogDescription class="text-[13px] text-gray-600 leading-relaxed">
                        Are you sure you want to remove
                        <span class="font-semibold text-gray-900">{{ deleteTarget?.name }}</span>?
                        Their account will be permanently deleted and they will lose all access.
                    </DialogDescription>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                    <button @click="deleteDialogOpen = false"
                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-[13px] font-semibold text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="doDelete"
                        class="flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-[13px] font-bold text-white hover:bg-red-700">
                        <Trash2 class="h-4 w-4" /> Remove
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
    Users, UserPlus, Pencil, Trash2, Mail, Phone,
    Lock, Save, Loader2, CheckCircle, AlertTriangle,
} from 'lucide-vue-next';
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({ name: 'AdminStaffIndex', layout: AdminLayout });

interface DocType  { id: number; name: string; }
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

// ── Sheet ─────────────────────────────────────────────────────────────────────
const sheetOpen  = ref(false);
const editTarget = ref<StaffItem | null>(null);

const form = useForm({
    first_name:            '',
    last_name:             '',
    email:                 '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
    document_type_ids:     [] as number[],
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
    form.first_name            = parts[0] ?? '';
    form.last_name             = parts.slice(1).join(' ');
    form.email                 = s.email;
    form.phone                 = s.phone ?? '';
    form.password              = '';
    form.password_confirmation = '';
    form.document_type_ids     = s.document_types.map(d => d.id);
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
    deleteTarget.value     = s;
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
