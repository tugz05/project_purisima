<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    Plus, User, Edit, Trash2, Calendar, Users, Briefcase, GraduationCap, Heart,
    Save, LoaderCircle, X, CheckCircle, Clock, XCircle, UserCheck, Bell,
} from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';

interface LinkedUser {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
}

interface HouseholdMember {
    id: number;
    linked_user_id?: number;
    linked_user?: LinkedUser;
    first_name: string;
    middle_name?: string;
    last_name: string;
    suffix?: string;
    relationship: string;
    birth_date?: string;
    sex?: string;
    civil_status?: string;
    occupation?: string;
    educational_attainment?: string;
    is_working: boolean;
    is_student: boolean;
    is_senior_citizen: boolean;
    is_pwd: boolean;
    notes?: string;
    invitation_status?: 'pending' | 'accepted' | 'declined';
    full_name?: string;
    age?: number;
    relationship_display?: string;
}

interface IncomingInvitation {
    id: number;
    inviter_name: string;
    inviter_purok?: string;
    relationship: string;
}

interface ResidentSuggestion {
    id: number;
    name: string;
    purok?: string;
    first_name: string;
    middle_name?: string;
    last_name: string;
    suffix?: string;
    birth_date?: string;
    sex?: string;
    civil_status?: string;
    occupation?: string;
    educational_attainment?: string;
}

interface Props {
    householdMembers: HouseholdMember[];
    incomingInvitations: IncomingInvitation[];
}

const props = defineProps<Props>();

const { residentHouseholdMembersBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = residentHouseholdMembersBreadcrumbs.value;

const selectedMember = ref<HouseholdMember | null>(null);
const viewSheetOpen = ref(false);
const createSheetOpen = ref(false);

// Autocomplete state
const searchQuery = ref('');
const searchResults = ref<ResidentSuggestion[]>([]);
const selectedResident = ref<ResidentSuggestion | null>(null);
const isSearching = ref(false);
const showDropdown = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let searchAbortController: AbortController | null = null;
const searchCache = new Map<string, ResidentSuggestion[]>();

const openViewSheet = (member: HouseholdMember) => {
    selectedMember.value = member;
    viewSheetOpen.value = true;
};

const deleteMember = (member: HouseholdMember) => {
    const name = member.full_name || `${member.first_name} ${member.last_name}`;
    if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
        router.delete(`/resident/household-members/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Household member deleted successfully!'),
            onError: () => toast.error('Failed to delete household member. Please try again.'),
        });
    }
};

const createForm = useForm({
    full_name: '',
    linked_user_id: null as number | null,
    relationship: 'child',
    birth_date: '',
    sex: '',
    civil_status: '',
    occupation: '',
    educational_attainment: '',
    is_working: false,
    is_student: false,
    is_senior_citizen: false,
    is_pwd: false,
    notes: '',
});

// Core search — called by the watcher; always receives the already-committed value.
const doSearch = (raw: string) => {
    const q = raw.trim();

    if (searchTimeout) clearTimeout(searchTimeout);
    if (searchAbortController) { searchAbortController.abort(); searchAbortController = null; }

    if (q === '') {
        searchResults.value = [];
        showDropdown.value = false;
        isSearching.value = false;
        return;
    }

    // Instant cache hit — no debounce, no spinner
    if (searchCache.has(q)) {
        searchResults.value = searchCache.get(q)!;
        showDropdown.value = searchResults.value.length > 0;
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        searchAbortController = new AbortController();
        try {
            const res = await fetch(
                `/resident/household-members/search-residents?q=${encodeURIComponent(q)}`,
                { signal: searchAbortController.signal, credentials: 'same-origin' }
            );
            if (!res.ok) { searchResults.value = []; return; }
            const data: ResidentSuggestion[] = await res.json();
            if (data.length > 0) searchCache.set(q, data); // only cache non-empty results
            searchResults.value = data;
            showDropdown.value = data.length > 0;
        } catch (err: unknown) {
            if (err instanceof Error && err.name !== 'AbortError') searchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 150);
};

const onInput = (e: Event) => {
    const val = (e.target as HTMLInputElement).value;
    searchQuery.value = val;
    if (selectedResident.value) {
        selectedResident.value = null;
        createForm.linked_user_id = null;
    }
    doSearch(val);
};

const hideDropdownDelayed = () => {
    setTimeout(() => { showDropdown.value = false; }, 150);
};

const selectResident = (resident: ResidentSuggestion) => {
    selectedResident.value = resident;
    createForm.linked_user_id = resident.id;
    const parts = [resident.first_name, resident.middle_name, resident.last_name, resident.suffix].filter(Boolean);
    searchQuery.value = parts.join(' ');
    createForm.full_name = searchQuery.value;
    createForm.birth_date = resident.birth_date ?? '';
    createForm.sex = resident.sex ?? '';
    createForm.civil_status = resident.civil_status ?? '';
    createForm.occupation = resident.occupation ?? '';
    createForm.educational_attainment = resident.educational_attainment ?? '';
    showDropdown.value = false;
    searchResults.value = [];
};

const clearSelection = () => {
    selectedResident.value = null;
    createForm.linked_user_id = null;
    searchQuery.value = '';
    createForm.full_name = '';
    createForm.birth_date = '';
    createForm.sex = '';
    createForm.civil_status = '';
    createForm.occupation = '';
    createForm.educational_attainment = '';
    if (searchTimeout) { clearTimeout(searchTimeout); searchTimeout = null; }
    if (searchAbortController) { searchAbortController.abort(); searchAbortController = null; }
    searchResults.value = [];
    showDropdown.value = false;
    isSearching.value = false;
};

const submitCreate = () => {
    if (!selectedResident.value) {
        createForm.full_name = searchQuery.value;
    }
    createForm.post('/resident/household-members', {
        preserveScroll: true,
        onSuccess: () => {
            const msg = selectedResident.value
                ? `Invitation sent to ${selectedResident.value.name}. They will be added once they accept.`
                : 'Household member added successfully!';
            toast.success(msg);
            createSheetOpen.value = false;
            nextTick(() => { createForm.reset(); clearSelection(); });
        },
        onError: () => {
            toast.error('Failed to add household member. Please check the form and try again.');
        },
    });
};

const respondingId = ref<number | null>(null);
const respondInvitation = (invitation: IncomingInvitation, action: 'accept' | 'decline') => {
    respondingId.value = invitation.id;
    router.post(`/resident/household-members/${invitation.id}/respond-invitation`, { action }, {
        preserveScroll: true,
        onSuccess: () => {
            const label = action === 'accept' ? 'accepted' : 'declined';
            toast.success(`You have ${label} the household invitation from ${invitation.inviter_name}.`);
        },
        onError: () => toast.error('Failed to respond to invitation. Please try again.'),
        onFinish: () => { respondingId.value = null; },
    });
};

watch(createSheetOpen, (open) => {
    if (!open) {
        if (searchAbortController) { searchAbortController.abort(); searchAbortController = null; }
        nextTick(() => { createForm.reset(); clearSelection(); });
    }
});

const formatDate = (date: string | null | undefined): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getRelationshipColor = (relationship: string): string => {
    const colors: Record<string, string> = {
        self: 'bg-blue-100 text-blue-800',
        spouse: 'bg-pink-100 text-pink-800',
        child: 'bg-green-100 text-green-800',
        parent: 'bg-purple-100 text-purple-800',
        sibling: 'bg-yellow-100 text-yellow-800',
        grandparent: 'bg-indigo-100 text-indigo-800',
        grandchild: 'bg-teal-100 text-teal-800',
        other: 'bg-gray-100 text-gray-800',
    };
    return colors[relationship] || colors['other'];
};

const invitationStatusLabel: Record<string, string> = {
    pending: 'Pending Acceptance',
    accepted: 'Linked Resident',
    declined: 'Invitation Declined',
};
const invitationStatusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-green-100 text-green-800',
    declined: 'bg-red-100 text-red-800',
};
</script>

<template>
    <Head title="Household Members" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">

                <!-- Header -->
                <div class="mb-6 md:mb-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 md:gap-6">
                        <div class="space-y-2">
                            <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                                Household Members
                            </h1>
                            <p class="text-lg md:text-xl text-gray-600 font-medium">Manage your household members information</p>
                        </div>
                        <Sheet v-model:open="createSheetOpen">
                            <SheetTrigger as-child>
                                <Button class="w-full sm:w-auto h-12 px-6 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white shadow-lg font-semibold transition-all duration-200">
                                    <Plus class="h-5 w-5 mr-2" />
                                    Add Member
                                </Button>
                            </SheetTrigger>
                            <SheetContent class="w-full sm:w-[600px] lg:w-[700px] overflow-y-auto p-0">
                                <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-5 shadow-sm">
                                    <SheetHeader>
                                        <SheetTitle class="text-2xl font-bold text-gray-900">Add Household Member</SheetTitle>
                                        <SheetDescription class="text-base text-gray-600 mt-2">
                                            Search a registered resident to send an invitation, or type a full name to add manually.
                                        </SheetDescription>
                                    </SheetHeader>
                                </div>

                                <div class="px-6 py-6">
                                    <form @submit.prevent="submitCreate" class="space-y-8">

                                        <!-- Full Name / Autocomplete -->
                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold text-gray-700">Full Name <span class="text-red-500">*</span></Label>
                                            <div class="relative">
                                                <div class="relative">
                                                    <input
                                                        :value="searchQuery"
                                                        type="text"
                                                        placeholder="Type a name to search registered residents…"
                                                        autocomplete="off"
                                                        class="flex h-11 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none placeholder:text-muted-foreground transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] pr-10"
                                                        @input="onInput"
                                                        @blur="hideDropdownDelayed"
                                                        @focus="showDropdown = searchResults.length > 0"
                                                    />
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                                                        <LoaderCircle v-if="isSearching" class="h-4 w-4 text-gray-400 animate-spin" />
                                                        <button
                                                            v-if="searchQuery && !isSearching"
                                                            type="button"
                                                            class="text-gray-400 hover:text-gray-600"
                                                            @click="clearSelection"
                                                        >
                                                            <X class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Dropdown -->
                                                <div
                                                    v-if="showDropdown"
                                                    class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 max-h-64 overflow-y-auto"
                                                >
                                                    <button
                                                        v-for="r in searchResults"
                                                        :key="r.id"
                                                        type="button"
                                                        class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-0 flex items-center gap-3"
                                                        @mousedown.prevent="selectResident(r)"
                                                    >
                                                        <div class="bg-blue-100 p-2 rounded-full shrink-0">
                                                            <User class="h-4 w-4 text-blue-600" />
                                                        </div>
                                                        <div>
                                                            <p class="font-semibold text-gray-900 text-sm">{{ r.name }}</p>
                                                            <p v-if="r.purok" class="text-xs text-gray-500">{{ r.purok }}</p>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Selected resident indicator -->
                                            <div v-if="selectedResident" class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                                                <UserCheck class="h-4 w-4 text-blue-600 shrink-0" />
                                                <span class="text-blue-800 font-medium">Registered resident selected — an invitation will be sent for their acceptance.</span>
                                            </div>
                                            <p v-else-if="searchQuery.length >= 1 && !isSearching && searchResults.length === 0" class="text-xs text-gray-500">
                                                No other registered resident matched — this name will be added as a manual entry.
                                            </p>
                                            <InputError :message="createForm.errors.full_name ?? createForm.errors.linked_user_id" />
                                        </div>

                                        <!-- Relationship and Personal Info -->
                                        <div class="space-y-6">
                                            <div class="border-b border-gray-200 pb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Relationship &amp; Demographics</h3>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Relationship <span class="text-red-500">*</span></Label>
                                                    <Select v-model="createForm.relationship" required>
                                                        <SelectTrigger class="h-11">
                                                            <SelectValue placeholder="Select relationship" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="self">Self (Household Head)</SelectItem>
                                                            <SelectItem value="spouse">Spouse</SelectItem>
                                                            <SelectItem value="child">Child</SelectItem>
                                                            <SelectItem value="parent">Parent</SelectItem>
                                                            <SelectItem value="sibling">Sibling</SelectItem>
                                                            <SelectItem value="grandparent">Grandparent</SelectItem>
                                                            <SelectItem value="grandchild">Grandchild</SelectItem>
                                                            <SelectItem value="other">Other</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="createForm.errors.relationship" />
                                                </div>

                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Birth Date</Label>
                                                    <Input v-model="createForm.birth_date" type="date" class="h-11" :disabled="!!selectedResident" />
                                                    <InputError :message="createForm.errors.birth_date" />
                                                </div>

                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Sex</Label>
                                                    <Select v-model="createForm.sex" :disabled="!!selectedResident">
                                                        <SelectTrigger class="h-11">
                                                            <SelectValue placeholder="Select sex" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="male">Male</SelectItem>
                                                            <SelectItem value="female">Female</SelectItem>
                                                            <SelectItem value="other">Other</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="createForm.errors.sex" />
                                                </div>

                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Civil Status</Label>
                                                    <Select v-model="createForm.civil_status" :disabled="!!selectedResident">
                                                        <SelectTrigger class="h-11">
                                                            <SelectValue placeholder="Select civil status" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="single">Single</SelectItem>
                                                            <SelectItem value="married">Married</SelectItem>
                                                            <SelectItem value="widowed">Widowed</SelectItem>
                                                            <SelectItem value="separated">Separated</SelectItem>
                                                            <SelectItem value="divorced">Divorced</SelectItem>
                                                            <SelectItem value="other">Other</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError :message="createForm.errors.civil_status" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Employment & Education (hidden for linked residents) -->
                                        <div v-if="!selectedResident" class="space-y-6">
                                            <div class="border-b border-gray-200 pb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Employment &amp; Education</h3>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Occupation</Label>
                                                    <Input v-model="createForm.occupation" type="text" class="h-11" placeholder="Enter occupation" />
                                                    <InputError :message="createForm.errors.occupation" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold text-gray-700">Educational Attainment</Label>
                                                    <Input v-model="createForm.educational_attainment" type="text" class="h-11" placeholder="e.g., College" />
                                                    <InputError :message="createForm.errors.educational_attainment" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Checkboxes (hidden for linked residents) -->
                                        <div v-if="!selectedResident" class="space-y-6">
                                            <div class="border-b border-gray-200 pb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Member Status</h3>
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_working = !createForm.is_working">
                                                    <input type="checkbox" v-model="createForm.is_working" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                                    <Label class="text-sm font-medium text-gray-700 cursor-pointer">Working</Label>
                                                </div>
                                                <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_student = !createForm.is_student">
                                                    <input type="checkbox" v-model="createForm.is_student" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                                    <Label class="text-sm font-medium text-gray-700 cursor-pointer">Student</Label>
                                                </div>
                                                <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_senior_citizen = !createForm.is_senior_citizen">
                                                    <input type="checkbox" v-model="createForm.is_senior_citizen" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                                    <Label class="text-sm font-medium text-gray-700 cursor-pointer">Senior Citizen</Label>
                                                </div>
                                                <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_pwd = !createForm.is_pwd">
                                                    <input type="checkbox" v-model="createForm.is_pwd" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                                    <Label class="text-sm font-medium text-gray-700 cursor-pointer">PWD</Label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div class="space-y-2">
                                            <div class="border-b border-gray-200 pb-4 mb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Additional Information</h3>
                                            </div>
                                            <Label class="text-sm font-semibold text-gray-700">Notes</Label>
                                            <Textarea v-model="createForm.notes" class="min-h-[80px] resize-none" rows="3" placeholder="Optional notes or remarks" />
                                            <InputError :message="createForm.errors.notes" />
                                        </div>
                                    </form>

                                    <!-- Footer Buttons -->
                                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 mt-6 -mx-6 -mb-6 shadow-lg">
                                        <div class="flex justify-end gap-3">
                                            <Button type="button" variant="outline" @click="createSheetOpen = false" class="h-11 px-6 font-medium">
                                                Cancel
                                            </Button>
                                            <Button
                                                type="submit"
                                                :disabled="createForm.processing || !searchQuery.trim()"
                                                @click="submitCreate"
                                                class="h-11 px-8 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold shadow-md"
                                            >
                                                <LoaderCircle v-if="createForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                                                <Save v-else class="h-4 w-4 mr-2" />
                                                {{ createForm.processing ? 'Saving…' : (selectedResident ? 'Send Invitation' : 'Add Member') }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>

                <!-- Incoming Invitations -->
                <div v-if="props.incomingInvitations.length > 0" class="mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <Bell class="h-5 w-5 text-amber-600" />
                        <h2 class="text-xl font-bold text-gray-900">Pending Invitations</h2>
                        <Badge class="bg-amber-100 text-amber-800">{{ props.incomingInvitations.length }}</Badge>
                    </div>
                    <div class="space-y-3">
                        <Card v-for="inv in props.incomingInvitations" :key="inv.id" class="border-amber-200 bg-amber-50/60 shadow-md rounded-xl overflow-hidden">
                            <CardContent class="p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-amber-100 p-3 rounded-full shrink-0">
                                            <UserCheck class="h-5 w-5 text-amber-700" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">
                                                <span class="text-amber-700">{{ inv.inviter_name }}</span> invited you to join their household
                                            </p>
                                            <p class="text-sm text-gray-600 mt-0.5">
                                                Relationship: <span class="font-medium capitalize">{{ inv.relationship }}</span>
                                                <span v-if="inv.inviter_purok" class="ml-2 text-gray-400">· {{ inv.inviter_purok }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <Button
                                            size="sm"
                                            class="h-9 px-4 bg-green-600 hover:bg-green-700 text-white"
                                            :disabled="respondingId === inv.id"
                                            @click="respondInvitation(inv, 'accept')"
                                        >
                                            <LoaderCircle v-if="respondingId === inv.id" class="h-3 w-3 mr-1 animate-spin" />
                                            <CheckCircle v-else class="h-3 w-3 mr-1" />
                                            Accept
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-9 px-4 border-red-300 text-red-700 hover:bg-red-50"
                                            :disabled="respondingId === inv.id"
                                            @click="respondInvitation(inv, 'decline')"
                                        >
                                            <XCircle class="h-3 w-3 mr-1" />
                                            Decline
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-10">
                    <Card class="shadow-lg border-0 bg-gradient-to-br from-blue-50 to-blue-100/50">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 mb-1">Total Members</p>
                                    <p class="text-3xl font-bold text-blue-800">{{ props.householdMembers.length }}</p>
                                </div>
                                <div class="bg-blue-200 p-3 rounded-full">
                                    <Users class="h-6 w-6 text-blue-700" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="shadow-lg border-0 bg-gradient-to-br from-green-50 to-green-100/50">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 mb-1">Working</p>
                                    <p class="text-3xl font-bold text-green-800">{{ props.householdMembers.filter(m => m.is_working).length }}</p>
                                </div>
                                <div class="bg-green-200 p-3 rounded-full">
                                    <Briefcase class="h-6 w-6 text-green-700" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="shadow-lg border-0 bg-gradient-to-br from-purple-50 to-purple-100/50">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 mb-1">Students</p>
                                    <p class="text-3xl font-bold text-purple-800">{{ props.householdMembers.filter(m => m.is_student).length }}</p>
                                </div>
                                <div class="bg-purple-200 p-3 rounded-full">
                                    <GraduationCap class="h-6 w-6 text-purple-700" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="shadow-lg border-0 bg-gradient-to-br from-orange-50 to-orange-100/50">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 mb-1">Senior Citizens</p>
                                    <p class="text-3xl font-bold text-orange-800">{{ props.householdMembers.filter(m => m.is_senior_citizen).length }}</p>
                                </div>
                                <div class="bg-orange-200 p-3 rounded-full">
                                    <Heart class="h-6 w-6 text-orange-700" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Members List -->
                <div class="space-y-4 md:space-y-6">
                    <Card
                        v-for="member in props.householdMembers"
                        :key="member.id"
                        class="shadow-xl hover:shadow-2xl transition-all duration-300 border-0 bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden group"
                        :class="{ 'opacity-70': member.invitation_status === 'declined' }"
                    >
                        <CardContent class="p-6 md:p-8">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                <div class="flex-1 space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-100 p-3 rounded-full">
                                                <User class="h-6 w-6 text-blue-600" />
                                            </div>
                                            <div>
                                                <h3 class="text-xl md:text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">
                                                    {{ member.full_name || `${member.first_name} ${member.middle_name ?? ''} ${member.last_name} ${member.suffix ?? ''}`.trim() }}
                                                </h3>
                                                <div class="flex flex-wrap gap-2 mt-2">
                                                    <Badge :class="getRelationshipColor(member.relationship)" class="px-3 py-1 rounded-full font-semibold">
                                                        {{ member.relationship_display || member.relationship }}
                                                    </Badge>
                                                    <Badge
                                                        v-if="member.invitation_status"
                                                        :class="invitationStatusColor[member.invitation_status]"
                                                        class="px-3 py-1 rounded-full font-semibold flex items-center gap-1"
                                                    >
                                                        <Clock v-if="member.invitation_status === 'pending'" class="h-3 w-3" />
                                                        <CheckCircle v-else-if="member.invitation_status === 'accepted'" class="h-3 w-3" />
                                                        <XCircle v-else class="h-3 w-3" />
                                                        {{ invitationStatusLabel[member.invitation_status] }}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                        <div v-if="member.birth_date" class="flex items-center gap-2 text-gray-600">
                                            <Calendar class="h-4 w-4" />
                                            <span><strong>Birth Date:</strong> {{ formatDate(member.birth_date) }}</span>
                                            <span v-if="member.age" class="text-gray-500">({{ member.age }} yrs)</span>
                                        </div>
                                        <div v-if="member.sex" class="flex items-center gap-2 text-gray-600">
                                            <span><strong>Sex:</strong> {{ member.sex.charAt(0).toUpperCase() + member.sex.slice(1) }}</span>
                                        </div>
                                        <div v-if="member.civil_status" class="flex items-center gap-2 text-gray-600">
                                            <span><strong>Civil Status:</strong> {{ member.civil_status.charAt(0).toUpperCase() + member.civil_status.slice(1) }}</span>
                                        </div>
                                        <div v-if="member.occupation" class="flex items-center gap-2 text-gray-600">
                                            <Briefcase class="h-4 w-4" />
                                            <span><strong>Occupation:</strong> {{ member.occupation }}</span>
                                        </div>
                                        <div v-if="member.educational_attainment" class="flex items-center gap-2 text-gray-600">
                                            <GraduationCap class="h-4 w-4" />
                                            <span><strong>Education:</strong> {{ member.educational_attainment }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Badge v-if="member.is_working" variant="default" class="bg-green-100 text-green-800">Working</Badge>
                                        <Badge v-if="member.is_student" variant="default" class="bg-purple-100 text-purple-800">Student</Badge>
                                        <Badge v-if="member.is_senior_citizen" variant="default" class="bg-orange-100 text-orange-800">Senior Citizen</Badge>
                                        <Badge v-if="member.is_pwd" variant="default" class="bg-red-100 text-red-800">PWD</Badge>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Button variant="outline" size="sm" @click="openViewSheet(member)" class="h-10 px-4">
                                        <User class="h-4 w-4 mr-2" />
                                        View
                                    </Button>
                                    <Link :href="`/resident/household-members/${member.id}/edit`">
                                        <Button variant="outline" size="sm" class="h-10 px-4">
                                            <Edit class="h-4 w-4 mr-2" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button variant="destructive" size="sm" @click="deleteMember(member)" class="h-10 px-4">
                                        <Trash2 class="h-4 w-4 mr-2" />
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div v-if="props.householdMembers.length === 0" class="text-center py-20">
                        <div class="bg-gradient-to-br from-white to-blue-50/50 rounded-3xl border-2 border-dashed border-blue-200 p-16 shadow-xl">
                            <div class="bg-blue-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                <Users class="h-12 w-12 text-blue-600" />
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-4">No household members</h3>
                            <p class="text-xl text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">Get started by adding your first household member using the "Add Member" button above.</p>
                            <Button @click="createSheetOpen = true" class="h-12 px-8 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white shadow-lg font-semibold">
                                <Plus class="h-5 w-5 mr-2" />
                                Add First Member
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Member Sheet -->
        <Sheet v-model:open="viewSheetOpen">
            <SheetContent v-if="selectedMember" class="sm:max-w-2xl overflow-y-auto">
                <SheetHeader>
                    <SheetTitle class="text-2xl font-bold">Member Details</SheetTitle>
                    <SheetDescription>View complete information about this household member</SheetDescription>
                </SheetHeader>

                <div class="mt-6 space-y-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Personal Information</h3>
                        <div class="space-y-3">
                            <div>
                                <Label class="text-sm font-medium text-gray-500 mb-1 block">Full Name</Label>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ selectedMember.full_name || `${selectedMember.first_name} ${selectedMember.middle_name ?? ''} ${selectedMember.last_name} ${selectedMember.suffix ?? ''}`.trim() }}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 mb-1 block">Relationship</Label>
                                <Badge :class="getRelationshipColor(selectedMember.relationship)" class="px-3 py-1">
                                    {{ selectedMember.relationship_display || selectedMember.relationship }}
                                </Badge>
                            </div>
                            <div v-if="selectedMember.invitation_status">
                                <Label class="text-sm font-medium text-gray-500 mb-1 block">Invitation Status</Label>
                                <Badge :class="invitationStatusColor[selectedMember.invitation_status]" class="px-3 py-1">
                                    {{ invitationStatusLabel[selectedMember.invitation_status] }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-if="selectedMember.birth_date" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <Calendar class="h-5 w-5 text-gray-500" />
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Birth Date</Label>
                                <p class="text-gray-900 font-medium">{{ formatDate(selectedMember.birth_date) }}</p>
                                <p v-if="selectedMember.age" class="text-sm text-gray-600">Age: {{ selectedMember.age }} years old</p>
                            </div>
                        </div>
                        <div v-if="selectedMember.sex" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <User class="h-5 w-5 text-gray-500" />
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Sex</Label>
                                <p class="text-gray-900 font-medium">{{ selectedMember.sex.charAt(0).toUpperCase() + selectedMember.sex.slice(1) }}</p>
                            </div>
                        </div>
                        <div v-if="selectedMember.civil_status" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <Heart class="h-5 w-5 text-gray-500" />
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Civil Status</Label>
                                <p class="text-gray-900 font-medium">{{ selectedMember.civil_status.charAt(0).toUpperCase() + selectedMember.civil_status.slice(1) }}</p>
                            </div>
                        </div>
                        <div v-if="selectedMember.occupation" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <Briefcase class="h-5 w-5 text-gray-500" />
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Occupation</Label>
                                <p class="text-gray-900 font-medium">{{ selectedMember.occupation }}</p>
                            </div>
                        </div>
                        <div v-if="selectedMember.educational_attainment" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <GraduationCap class="h-5 w-5 text-gray-500" />
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Educational Attainment</Label>
                                <p class="text-gray-900 font-medium">{{ selectedMember.educational_attainment }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Badge v-if="selectedMember.is_working" variant="default" class="bg-green-100 text-green-800 px-4 py-2">Working</Badge>
                        <Badge v-if="selectedMember.is_student" variant="default" class="bg-purple-100 text-purple-800 px-4 py-2">Student</Badge>
                        <Badge v-if="selectedMember.is_senior_citizen" variant="default" class="bg-orange-100 text-orange-800 px-4 py-2">Senior Citizen</Badge>
                        <Badge v-if="selectedMember.is_pwd" variant="default" class="bg-red-100 text-red-800 px-4 py-2">Person with Disability</Badge>
                    </div>

                    <div v-if="selectedMember.notes" class="bg-gray-50 rounded-lg p-4">
                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Notes</Label>
                        <p class="text-gray-700">{{ selectedMember.notes }}</p>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <Link :href="`/resident/household-members/${selectedMember.id}/edit`" class="flex-1">
                            <Button class="w-full">
                                <Edit class="h-4 w-4 mr-2" />
                                Edit Member
                            </Button>
                        </Link>
                        <Button variant="destructive" class="flex-1" @click="deleteMember(selectedMember); viewSheetOpen = false;">
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </ResidentLayout>
</template>
