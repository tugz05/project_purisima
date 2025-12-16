<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, nextTick } from 'vue';
import resident from '@/routes/resident';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, User, Edit, Trash2, Calendar, Users, Briefcase, GraduationCap, Heart, Save, LoaderCircle } from 'lucide-vue-next';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';

interface HouseholdMember {
    id: number;
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
    full_name?: string;
    age?: number;
    relationship_display?: string;
}

interface Props {
    householdMembers: HouseholdMember[];
}

const props = defineProps<Props>();

const { residentHouseholdMembersBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = residentHouseholdMembersBreadcrumbs.value;

const selectedMember = ref<HouseholdMember | null>(null);
const viewSheetOpen = ref(false);
const createSheetOpen = ref(false);

const openViewSheet = (member: HouseholdMember) => {
    selectedMember.value = member;
    viewSheetOpen.value = true;
};

const deleteMember = (member: HouseholdMember) => {
    if (confirm(`Are you sure you want to delete ${member.full_name || member.first_name + ' ' + member.last_name}? This action cannot be undone.`)) {
        router.delete(`/resident/household-members/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Household member deleted successfully!');
            },
            onError: () => {
                toast.error('Failed to delete household member. Please try again.');
            }
        });
    }
};

const createForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
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

const submitCreate = () => {
    createForm.post('/resident/household-members', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Household member added successfully!');
            createSheetOpen.value = false;
            nextTick(() => {
                createForm.reset();
            });
        },
        onError: () => {
            toast.error('Failed to add household member. Please check the form and try again.');
        }
    });
};

// Watch for sheet close to reset form
watch(createSheetOpen, (newValue) => {
    if (!newValue) {
        nextTick(() => {
            createForm.reset();
        });
    }
});

const formatDate = (date: string | null | undefined): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

const getRelationshipColor = (relationship: string): string => {
    const colors: Record<string, string> = {
        'self': 'bg-blue-100 text-blue-800',
        'spouse': 'bg-pink-100 text-pink-800',
        'child': 'bg-green-100 text-green-800',
        'parent': 'bg-purple-100 text-purple-800',
        'sibling': 'bg-yellow-100 text-yellow-800',
        'grandparent': 'bg-indigo-100 text-indigo-800',
        'grandchild': 'bg-teal-100 text-teal-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return colors[relationship] || colors['other'];
};
</script>

<template>
    <Head title="Household Members" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header Section -->
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
                                        <SheetDescription class="text-base text-gray-600 mt-2">Enter the details of the household member to add them to your household</SheetDescription>
                                    </SheetHeader>
                                </div>

                                <div class="px-6 py-6">
                                    <form @submit.prevent="submitCreate" class="space-y-8">
                                    <!-- Name Fields -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div>
                                                <Label for="create_first_name" class="text-sm font-medium text-gray-700">First Name *</Label>
                                                <Input
                                                    id="create_first_name"
                                                    v-model="createForm.first_name"
                                                    type="text"
                                                    required
                                                    class="mt-1"
                                                    placeholder="First name"
                                                />
                                                <InputError :message="createForm.errors.first_name" />
                                            </div>
                                            <div>
                                                <Label for="create_middle_name" class="text-sm font-medium text-gray-700">Middle Name</Label>
                                                <Input
                                                    id="create_middle_name"
                                                    v-model="createForm.middle_name"
                                                    type="text"
                                                    class="mt-1"
                                                    placeholder="Middle name"
                                                />
                                                <InputError :message="createForm.errors.middle_name" />
                                            </div>
                                            <div>
                                                <Label for="create_last_name" class="text-sm font-medium text-gray-700">Last Name *</Label>
                                                <Input
                                                    id="create_last_name"
                                                    v-model="createForm.last_name"
                                                    type="text"
                                                    required
                                                    class="mt-1"
                                                    placeholder="Last name"
                                                />
                                                <InputError :message="createForm.errors.last_name" />
                                            </div>
                                            <div>
                                                <Label for="create_suffix" class="text-sm font-medium text-gray-700">Suffix</Label>
                                                <Input
                                                    id="create_suffix"
                                                    v-model="createForm.suffix"
                                                    type="text"
                                                    class="mt-1"
                                                    placeholder="Jr., Sr., II, etc."
                                                />
                                                <InputError :message="createForm.errors.suffix" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Relationship and Personal Info Section -->
                                    <div class="space-y-6">
                                        <div class="border-b border-gray-200 pb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Relationship & Demographics</h3>
                                            <p class="text-sm text-gray-500 mt-1">Relationship to household head and personal details</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-2">
                                                <Label for="create_relationship" class="text-sm font-semibold text-gray-700">Relationship <span class="text-red-500">*</span></Label>
                                                <Select v-model="createForm.relationship" required>
                                                    <SelectTrigger class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
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
                                                <Label for="create_birth_date" class="text-sm font-semibold text-gray-700">Birth Date</Label>
                                                <Input
                                                    id="create_birth_date"
                                                    v-model="createForm.birth_date"
                                                    type="date"
                                                    class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                />
                                                <InputError :message="createForm.errors.birth_date" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="create_sex" class="text-sm font-semibold text-gray-700">Sex</Label>
                                                <Select v-model="createForm.sex">
                                                    <SelectTrigger class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
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
                                                <Label for="create_civil_status" class="text-sm font-semibold text-gray-700">Civil Status</Label>
                                                <Select v-model="createForm.civil_status">
                                                    <SelectTrigger class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
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

                                    <!-- Occupation and Education Section -->
                                    <div class="space-y-6">
                                        <div class="border-b border-gray-200 pb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Employment & Education</h3>
                                            <p class="text-sm text-gray-500 mt-1">Professional and educational background</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-2">
                                                <Label for="create_occupation" class="text-sm font-semibold text-gray-700">Occupation</Label>
                                                <Input
                                                    id="create_occupation"
                                                    v-model="createForm.occupation"
                                                    type="text"
                                                    class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Enter occupation"
                                                />
                                                <InputError :message="createForm.errors.occupation" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="create_educational_attainment" class="text-sm font-semibold text-gray-700">Educational Attainment</Label>
                                                <Input
                                                    id="create_educational_attainment"
                                                    v-model="createForm.educational_attainment"
                                                    type="text"
                                                    class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="e.g., High School, College, etc."
                                                />
                                                <InputError :message="createForm.errors.educational_attainment" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Checkboxes Section -->
                                    <div class="space-y-6">
                                        <div class="border-b border-gray-200 pb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Member Status</h3>
                                            <p class="text-sm text-gray-500 mt-1">Select applicable status categories</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_working = !createForm.is_working">
                                                <input
                                                    type="checkbox"
                                                    id="create_is_working"
                                                    v-model="createForm.is_working"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                />
                                                <Label for="create_is_working" class="text-sm font-medium text-gray-700 cursor-pointer">Working</Label>
                                            </div>
                                            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_student = !createForm.is_student">
                                                <input
                                                    type="checkbox"
                                                    id="create_is_student"
                                                    v-model="createForm.is_student"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                />
                                                <Label for="create_is_student" class="text-sm font-medium text-gray-700 cursor-pointer">Student</Label>
                                            </div>
                                            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_senior_citizen = !createForm.is_senior_citizen">
                                                <input
                                                    type="checkbox"
                                                    id="create_is_senior_citizen"
                                                    v-model="createForm.is_senior_citizen"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                />
                                                <Label for="create_is_senior_citizen" class="text-sm font-medium text-gray-700 cursor-pointer">Senior Citizen</Label>
                                            </div>
                                            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors cursor-pointer" @click="createForm.is_pwd = !createForm.is_pwd">
                                                <input
                                                    type="checkbox"
                                                    id="create_is_pwd"
                                                    v-model="createForm.is_pwd"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                />
                                                <Label for="create_is_pwd" class="text-sm font-medium text-gray-700 cursor-pointer">PWD</Label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes Section -->
                                    <div class="space-y-6">
                                        <div class="border-b border-gray-200 pb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Additional Information</h3>
                                            <p class="text-sm text-gray-500 mt-1">Any additional notes or remarks</p>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <Label for="create_notes" class="text-sm font-semibold text-gray-700">Notes</Label>
                                            <Textarea
                                                id="create_notes"
                                                v-model="createForm.notes"
                                                class="min-h-[100px] border-gray-300 focus:border-blue-500 focus:ring-blue-500 resize-none"
                                                rows="4"
                                                placeholder="Enter any additional notes or remarks about this household member (optional)"
                                            />
                                            <InputError :message="createForm.errors.notes" />
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Submit Button Footer -->
                                <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 mt-6 -mx-6 -mb-6 shadow-lg">
                                    <div class="flex justify-end gap-3">
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            @click="createSheetOpen = false"
                                            class="h-11 px-6 font-medium"
                                        >
                                            Cancel
                                        </Button>
                                        <Button 
                                            type="submit" 
                                            :disabled="createForm.processing" 
                                            @click="submitCreate"
                                            class="h-11 px-8 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold shadow-md hover:shadow-lg transition-all"
                                        >
                                            <LoaderCircle v-if="createForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                                            <Save v-else class="h-4 w-4 mr-2" />
                                            {{ createForm.processing ? 'Adding Member...' : 'Add Member' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            </SheetContent>
                        </Sheet>
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
                    <Card v-for="member in props.householdMembers" :key="member.id" class="shadow-xl hover:shadow-2xl transition-all duration-300 border-0 bg-white/90 backdrop-blur-sm rounded-2xl overflow-hidden group">
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
                                                    {{ member.full_name || `${member.first_name} ${member.middle_name || ''} ${member.last_name} ${member.suffix || ''}`.trim() }}
                                                </h3>
                                                <Badge :class="getRelationshipColor(member.relationship)" class="mt-2 w-fit px-3 py-1 rounded-full font-semibold">
                                                    {{ member.relationship_display || member.relationship }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                        <div v-if="member.birth_date" class="flex items-center gap-2 text-gray-600">
                                            <Calendar class="h-4 w-4" />
                                            <span><strong>Birth Date:</strong> {{ formatDate(member.birth_date) }}</span>
                                            <span v-if="member.age" class="text-gray-500">({{ member.age }} years old)</span>
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
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        @click="openViewSheet(member)"
                                        class="h-10 px-4"
                                    >
                                        <User class="h-4 w-4 mr-2" />
                                        View
                                    </Button>
                                    <Link :href="`/resident/household-members/${member.id}/edit`">
                                        <Button variant="outline" size="sm" class="h-10 px-4">
                                            <Edit class="h-4 w-4 mr-2" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button 
                                        variant="destructive" 
                                        size="sm"
                                        @click="deleteMember(member)"
                                        class="h-10 px-4"
                                    >
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
                    <!-- Name and Relationship -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Personal Information</h3>
                        <div class="space-y-3">
                            <div>
                                <Label class="text-sm font-medium text-gray-500 mb-1 block">Full Name</Label>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ selectedMember.full_name || `${selectedMember.first_name} ${selectedMember.middle_name || ''} ${selectedMember.last_name} ${selectedMember.suffix || ''}`.trim() }}
                                </p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 mb-1 block">Relationship</Label>
                                <Badge :class="getRelationshipColor(selectedMember.relationship)" class="px-3 py-1">
                                    {{ selectedMember.relationship_display || selectedMember.relationship }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
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

                    <!-- Status Badges -->
                    <div class="flex flex-wrap gap-2">
                        <Badge v-if="selectedMember.is_working" variant="default" class="bg-green-100 text-green-800 px-4 py-2">Working</Badge>
                        <Badge v-if="selectedMember.is_student" variant="default" class="bg-purple-100 text-purple-800 px-4 py-2">Student</Badge>
                        <Badge v-if="selectedMember.is_senior_citizen" variant="default" class="bg-orange-100 text-orange-800 px-4 py-2">Senior Citizen</Badge>
                        <Badge v-if="selectedMember.is_pwd" variant="default" class="bg-red-100 text-red-800 px-4 py-2">Person with Disability</Badge>
                    </div>

                    <!-- Notes -->
                    <div v-if="selectedMember.notes" class="bg-gray-50 rounded-lg p-4">
                        <Label class="text-sm font-medium text-gray-500 mb-2 block">Notes</Label>
                        <p class="text-gray-700">{{ selectedMember.notes }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t">
                        <Link :href="`/resident/household-members/${selectedMember.id}/edit`" class="flex-1">
                            <Button class="w-full">
                                <Edit class="h-4 w-4 mr-2" />
                                Edit Member
                            </Button>
                        </Link>
                        <Button 
                            variant="destructive" 
                            class="flex-1"
                            @click="deleteMember(selectedMember); viewSheetOpen = false;"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete
                        </Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </ResidentLayout>
</template>

