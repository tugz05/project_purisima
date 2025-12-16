<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Save, LoaderCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import resident from '@/routes/resident';
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
}

interface Props {
    householdMember: HouseholdMember;
}

const props = defineProps<Props>();

const { residentHouseholdMemberEditBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = residentHouseholdMemberEditBreadcrumbs.value(props.householdMember.id.toString());

const form = useForm({
    first_name: props.householdMember.first_name || '',
    middle_name: props.householdMember.middle_name || '',
    last_name: props.householdMember.last_name || '',
    suffix: props.householdMember.suffix || '',
    relationship: props.householdMember.relationship || 'child',
    birth_date: props.householdMember.birth_date || '',
    sex: props.householdMember.sex || '',
    civil_status: props.householdMember.civil_status || '',
    occupation: props.householdMember.occupation || '',
    educational_attainment: props.householdMember.educational_attainment || '',
    is_working: props.householdMember.is_working || false,
    is_student: props.householdMember.is_student || false,
    is_senior_citizen: props.householdMember.is_senior_citizen || false,
    is_pwd: props.householdMember.is_pwd || false,
    notes: props.householdMember.notes || '',
});

const submit = () => {
    form.put(`/resident/household-members/${props.householdMember.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Household member updated successfully!');
        },
        onError: () => {
            toast.error('Failed to update household member. Please check the form and try again.');
        }
    });
};
</script>

<template>
    <Head title="Edit Household Member" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-4xl">
                <!-- Header -->
                <div class="mb-6">
                    <Link href="/resident/household-members" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors mb-4">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Household Members
                    </Link>
                    <h1 class="text-2xl md:text-4xl font-bold text-gray-900 mb-2">Edit Household Member</h1>
                    <p class="text-lg text-gray-600">Update member information</p>
                </div>

                <!-- Form -->
                <Card class="shadow-xl border-0">
                    <CardHeader>
                        <CardTitle>Member Information</CardTitle>
                        <CardDescription>Update the details of the household member</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Name Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <Label for="first_name" class="text-sm font-medium text-gray-700">First Name *</Label>
                                        <Input
                                            id="first_name"
                                            v-model="form.first_name"
                                            type="text"
                                            required
                                            class="mt-1"
                                            placeholder="First name"
                                        />
                                        <InputError :message="form.errors.first_name" />
                                    </div>
                                    <div>
                                        <Label for="middle_name" class="text-sm font-medium text-gray-700">Middle Name</Label>
                                        <Input
                                            id="middle_name"
                                            v-model="form.middle_name"
                                            type="text"
                                            class="mt-1"
                                            placeholder="Middle name"
                                        />
                                        <InputError :message="form.errors.middle_name" />
                                    </div>
                                    <div>
                                        <Label for="last_name" class="text-sm font-medium text-gray-700">Last Name *</Label>
                                        <Input
                                            id="last_name"
                                            v-model="form.last_name"
                                            type="text"
                                            required
                                            class="mt-1"
                                            placeholder="Last name"
                                        />
                                        <InputError :message="form.errors.last_name" />
                                    </div>
                                    <div>
                                        <Label for="suffix" class="text-sm font-medium text-gray-700">Suffix</Label>
                                        <Input
                                            id="suffix"
                                            v-model="form.suffix"
                                            type="text"
                                            class="mt-1"
                                            placeholder="Jr., Sr., II, etc."
                                        />
                                        <InputError :message="form.errors.suffix" />
                                    </div>
                                </div>
                            </div>

                            <!-- Relationship and Personal Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label for="relationship" class="text-sm font-medium text-gray-700">Relationship *</Label>
                                    <Select v-model="form.relationship" required>
                                        <SelectTrigger class="mt-1">
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
                                    <InputError :message="form.errors.relationship" />
                                </div>

                                <div>
                                    <Label for="birth_date" class="text-sm font-medium text-gray-700">Birth Date</Label>
                                    <Input
                                        id="birth_date"
                                        v-model="form.birth_date"
                                        type="date"
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.birth_date" />
                                </div>

                                <div>
                                    <Label for="sex" class="text-sm font-medium text-gray-700">Sex</Label>
                                    <Select v-model="form.sex">
                                        <SelectTrigger class="mt-1">
                                            <SelectValue placeholder="Select sex" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.sex" />
                                </div>

                                <div>
                                    <Label for="civil_status" class="text-sm font-medium text-gray-700">Civil Status</Label>
                                    <Select v-model="form.civil_status">
                                        <SelectTrigger class="mt-1">
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
                                    <InputError :message="form.errors.civil_status" />
                                </div>
                            </div>

                            <!-- Occupation and Education -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label for="occupation" class="text-sm font-medium text-gray-700">Occupation</Label>
                                    <Input
                                        id="occupation"
                                        v-model="form.occupation"
                                        type="text"
                                        class="mt-1"
                                        placeholder="Occupation"
                                    />
                                    <InputError :message="form.errors.occupation" />
                                </div>

                                <div>
                                    <Label for="educational_attainment" class="text-sm font-medium text-gray-700">Educational Attainment</Label>
                                    <Input
                                        id="educational_attainment"
                                        v-model="form.educational_attainment"
                                        type="text"
                                        class="mt-1"
                                        placeholder="e.g., High School, College, etc."
                                    />
                                    <InputError :message="form.errors.educational_attainment" />
                                </div>
                            </div>

                            <!-- Status Checkboxes -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium text-gray-700">Status</Label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="is_working"
                                            v-model="form.is_working"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <Label for="is_working" class="text-sm font-normal cursor-pointer">Working</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="is_student"
                                            v-model="form.is_student"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <Label for="is_student" class="text-sm font-normal cursor-pointer">Student</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="is_senior_citizen"
                                            v-model="form.is_senior_citizen"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <Label for="is_senior_citizen" class="text-sm font-normal cursor-pointer">Senior Citizen</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="is_pwd"
                                            v-model="form.is_pwd"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <Label for="is_pwd" class="text-sm font-normal cursor-pointer">PWD</Label>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <Label for="notes" class="text-sm font-medium text-gray-700">Notes</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1"
                                    rows="3"
                                    placeholder="Additional notes about this member (optional)"
                                />
                                <InputError :message="form.errors.notes" />
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <Link href="/resident/household-members">
                                    <Button type="button" variant="outline">Cancel</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing" class="bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600">
                                    <LoaderCircle v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                                    <Save v-else class="h-4 w-4 mr-2" />
                                    {{ form.processing ? 'Updating...' : 'Update Member' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </ResidentLayout>
</template>

