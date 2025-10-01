<template>
  <ResidentResponsiveLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white p-8 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="p-4 bg-white/20 rounded-2xl backdrop-blur-sm shadow-lg">
              <Users class="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold mb-2">Create Household Profile</h1>
              <p class="text-blue-100 text-lg">Add your family information and income details</p>
            </div>
          </div>

          <Button
            type="button"
            variant="outline"
            @click="goBack"
            class="flex items-center gap-3 px-6 py-3 text-lg border-2 border-white/30 hover:border-white/50 bg-white/10 hover:bg-white/20 text-white backdrop-blur-sm transition-all duration-200"
          >
            <ArrowLeft class="w-5 h-5" />
            Back
          </Button>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Profile Completion Progress</h3>
          <span class="text-sm text-gray-600">{{ Math.round(progressPercentage) }}% Complete</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-gradient-to-r from-blue-600 to-purple-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="space-y-8">
        <!-- Household Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-100 rounded-lg">
              <Home class="w-5 h-5 text-blue-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Household Information</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <Label for="household_head_name" class="block mb-3 text-sm font-medium text-gray-700">Household Head Name *</Label>
              <Input
                id="household_head_name"
                v-model="form.household_head_name"
                placeholder="Enter household head name"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.household_head_name }"
              />
              <p v-if="form.errors.household_head_name" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.household_head_name }}
              </p>
            </div>

            <div>
              <Label for="household_head_relationship" class="block mb-3 text-sm font-medium text-gray-700">Relationship to Head *</Label>
              <Select v-model="form.household_head_relationship">
                <SelectTrigger class="mb-2">
                  <SelectValue placeholder="Select relationship" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="self">Self (Household Head)</SelectItem>
                  <SelectItem value="spouse">Spouse</SelectItem>
                  <SelectItem value="parent">Parent</SelectItem>
                  <SelectItem value="child">Child</SelectItem>
                  <SelectItem value="sibling">Sibling</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.household_head_relationship" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.household_head_relationship }}
              </p>
            </div>

            <div>
              <Label for="total_family_members" class="block mb-3 text-sm font-medium text-gray-700">Total Family Members *</Label>
              <Input
                id="total_family_members"
                v-model.number="form.total_family_members"
                type="number"
                min="1"
                placeholder="Enter total family members"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.total_family_members }"
              />
              <p v-if="form.errors.total_family_members" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.total_family_members }}
              </p>
            </div>

            <div>
              <Label for="working_members" class="block mb-3 text-sm font-medium text-gray-700">Working Members</Label>
              <Input
                id="working_members"
                v-model.number="form.working_members"
                type="number"
                min="0"
                placeholder="Enter working members count"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.working_members }"
              />
              <p v-if="form.errors.working_members" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.working_members }}
              </p>
            </div>

            <div>
              <Label for="dependent_members" class="block mb-3 text-sm font-medium text-gray-700">Dependent Members</Label>
              <Input
                id="dependent_members"
                v-model.number="form.dependent_members"
                type="number"
                min="0"
                placeholder="Enter dependent members count"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.dependent_members }"
              />
              <p v-if="form.errors.dependent_members" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.dependent_members }}
              </p>
            </div>
          </div>
        </div>

        <!-- Income Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-green-100 rounded-lg">
              <DollarSign class="w-5 h-5 text-green-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Income Information</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label for="monthly_income" class="block mb-3 text-sm font-medium text-gray-700">Monthly Income (₱)</Label>
              <Input
                id="monthly_income"
                v-model.number="form.monthly_income"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter monthly income"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.monthly_income }"
              />
              <p v-if="form.errors.monthly_income" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.monthly_income }}
              </p>
            </div>

            <div>
              <Label for="income_source" class="block mb-3 text-sm font-medium text-gray-700">Income Source</Label>
              <Select v-model="form.income_source">
                <SelectTrigger class="mb-2">
                  <SelectValue placeholder="Select income source" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="employment">Employment</SelectItem>
                  <SelectItem value="business">Business</SelectItem>
                  <SelectItem value="pension">Pension</SelectItem>
                  <SelectItem value="remittance">Remittance</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.income_source" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.income_source }}
              </p>
            </div>

            <div class="md:col-span-2">
              <Label for="income_source_details" class="block mb-3 text-sm font-medium text-gray-700">Income Source Details</Label>
              <Textarea
                id="income_source_details"
                v-model="form.income_source_details"
                placeholder="Provide additional details about your income source"
                rows="3"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.income_source_details }"
              />
              <p v-if="form.errors.income_source_details" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.income_source_details }}
              </p>
            </div>
          </div>
        </div>

        <!-- Housing Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-purple-100 rounded-lg">
              <Building class="w-5 h-5 text-purple-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Housing Information</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label for="housing_type" class="block mb-3 text-sm font-medium text-gray-700">Housing Type</Label>
              <Select v-model="form.housing_type">
                <SelectTrigger class="mb-2">
                  <SelectValue placeholder="Select housing type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="owned">Owned</SelectItem>
                  <SelectItem value="rented">Rented</SelectItem>
                  <SelectItem value="borrowed">Borrowed</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.housing_type" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.housing_type }}
              </p>
            </div>

            <div class="md:col-span-2">
              <Label for="housing_details" class="block mb-3 text-sm font-medium text-gray-700">Housing Details</Label>
              <Textarea
                id="housing_details"
                v-model="form.housing_details"
                placeholder="Provide additional details about your housing situation"
                rows="3"
                class="mb-2"
                :class="{ 'border-red-500': form.errors.housing_details }"
              />
              <p v-if="form.errors.housing_details" class="text-red-500 text-sm mt-2 mb-4">
                {{ form.errors.housing_details }}
              </p>
            </div>
          </div>
        </div>

        <!-- Assets Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-orange-100 rounded-lg">
              <Car class="w-5 h-5 text-orange-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Assets & Properties</h3>
          </div>

          <div class="space-y-6">
            <!-- Vehicle -->
            <div class="space-y-4">
              <div class="flex items-center gap-3">
                <Switch
                  id="has_vehicle"
                  v-model:checked="form.has_vehicle"
                />
                <Label for="has_vehicle" class="text-base font-medium">Has Vehicle</Label>
              </div>

              <div v-if="form.has_vehicle" class="mt-4">
                <Label for="vehicle_details" class="block mb-3 text-sm font-medium text-gray-700">Vehicle Details</Label>
                <Textarea
                  id="vehicle_details"
                  v-model="form.vehicle_details"
                  placeholder="Describe your vehicle(s)"
                  rows="2"
                  class="mb-2"
                  :class="{ 'border-red-500': form.errors.vehicle_details }"
                />
                <p v-if="form.errors.vehicle_details" class="text-red-500 text-sm mt-2 mb-4">
                  {{ form.errors.vehicle_details }}
                </p>
              </div>
            </div>

            <!-- Livestock -->
            <div class="space-y-4">
              <div class="flex items-center gap-3">
                <Switch
                  id="has_livestock"
                  v-model:checked="form.has_livestock"
                />
                <Label for="has_livestock" class="text-base font-medium">Has Livestock</Label>
              </div>

              <div v-if="form.has_livestock" class="mt-4">
                <Label for="livestock_details" class="block mb-3 text-sm font-medium text-gray-700">Livestock Details</Label>
                <Textarea
                  id="livestock_details"
                  v-model="form.livestock_details"
                  placeholder="Describe your livestock"
                  rows="2"
                  class="mb-2"
                  :class="{ 'border-red-500': form.errors.livestock_details }"
                />
                <p v-if="form.errors.livestock_details" class="text-red-500 text-sm mt-2 mb-4">
                  {{ form.errors.livestock_details }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Family Members Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-pink-100 rounded-lg">
                <Users class="w-5 h-5 text-pink-600" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900">Family Members</h3>
            </div>
            <Button
              type="button"
              variant="outline"
              @click="addFamilyMember"
              class="flex items-center gap-2"
            >
              <Plus class="w-4 h-4" />
              Add Member
            </Button>
          </div>

          <div v-if="form.family_members.length === 0" class="text-center py-8 text-gray-500">
            <Users class="w-12 h-12 mx-auto mb-4 text-gray-300" />
            <p>No family members added yet. Click "Add Member" to get started.</p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(member, index) in form.family_members"
              :key="index"
              class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200"
            >
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold text-gray-900">Family Member {{ index + 1 }}</h4>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="removeFamilyMember(index)"
                  class="text-red-600 hover:text-red-700 hover:bg-red-50"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Basic Information -->
                <div>
                  <Label :for="`member_${index}_first_name`" class="block mb-3 text-sm font-medium text-gray-700">First Name *</Label>
                  <Input
                    :id="`member_${index}_first_name`"
                    v-model="member.first_name"
                    placeholder="Enter first name"
                    class="mb-2"
                    :class="{ 'border-red-500': form.errors[`family_members.${index}.first_name`] }"
                  />
                  <p v-if="form.errors[`family_members.${index}.first_name`]" class="text-red-500 text-sm mt-2 mb-4">
                    {{ form.errors[`family_members.${index}.first_name`] }}
                  </p>
                </div>

                <div>
                  <Label :for="`member_${index}_middle_name`" class="block mb-3 text-sm font-medium text-gray-700">Middle Name</Label>
                  <Input
                    :id="`member_${index}_middle_name`"
                    v-model="member.middle_name"
                    placeholder="Enter middle name"
                    class="mb-2"
                  />
                </div>

                <div>
                  <Label :for="`member_${index}_last_name`" class="block mb-3 text-sm font-medium text-gray-700">Last Name *</Label>
                  <Input
                    :id="`member_${index}_last_name`"
                    v-model="member.last_name"
                    placeholder="Enter last name"
                    class="mb-2"
                    :class="{ 'border-red-500': form.errors[`family_members.${index}.last_name`] }"
                  />
                  <p v-if="form.errors[`family_members.${index}.last_name`]" class="text-red-500 text-sm mt-2 mb-4">
                    {{ form.errors[`family_members.${index}.last_name`] }}
                  </p>
                </div>

                <div>
                  <Label :for="`member_${index}_relationship`" class="block mb-3 text-sm font-medium text-gray-700">Relationship to Head *</Label>
                  <Select v-model="member.relationship_to_head">
                    <SelectTrigger class="mb-2">
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
                  <p v-if="form.errors[`family_members.${index}.relationship_to_head`]" class="text-red-500 text-sm mt-2 mb-4">
                    {{ form.errors[`family_members.${index}.relationship_to_head`] }}
                  </p>
                </div>

                <div>
                  <Label :for="`member_${index}_birth_date`" class="block mb-3 text-sm font-medium text-gray-700">Birth Date</Label>
                  <Input
                    :id="`member_${index}_birth_date`"
                    v-model="member.birth_date"
                    type="date"
                    class="mb-2"
                  />
                </div>

                <div>
                  <Label :for="`member_${index}_sex`" class="block mb-3 text-sm font-medium text-gray-700">Sex</Label>
                  <Select v-model="member.sex">
                    <SelectTrigger class="mb-2">
                      <SelectValue placeholder="Select sex" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="male">Male</SelectItem>
                      <SelectItem value="female">Female</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label :for="`member_${index}_civil_status`" class="block mb-3 text-sm font-medium text-gray-700">Civil Status</Label>
                  <Select v-model="member.civil_status">
                    <SelectTrigger class="mb-2">
                      <SelectValue placeholder="Select civil status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="single">Single</SelectItem>
                      <SelectItem value="married">Married</SelectItem>
                      <SelectItem value="widowed">Widowed</SelectItem>
                      <SelectItem value="separated">Separated</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label :for="`member_${index}_educational_attainment`" class="block mb-3 text-sm font-medium text-gray-700">Educational Attainment</Label>
                  <Select v-model="member.educational_attainment">
                    <SelectTrigger class="mb-2">
                      <SelectValue placeholder="Select education" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No formal education</SelectItem>
                      <SelectItem value="elementary">Elementary</SelectItem>
                      <SelectItem value="high_school">High School</SelectItem>
                      <SelectItem value="college">College</SelectItem>
                      <SelectItem value="graduate">Graduate School</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label :for="`member_${index}_occupation`" class="block mb-3 text-sm font-medium text-gray-700">Occupation</Label>
                  <Input
                    :id="`member_${index}_occupation`"
                    v-model="member.occupation"
                    placeholder="Enter occupation"
                    class="mb-2"
                  />
                </div>

                <div>
                  <Label :for="`member_${index}_employment_status`" class="block mb-3 text-sm font-medium text-gray-700">Employment Status</Label>
                  <Select v-model="member.employment_status">
                    <SelectTrigger class="mb-2">
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="employed">Employed</SelectItem>
                      <SelectItem value="unemployed">Unemployed</SelectItem>
                      <SelectItem value="student">Student</SelectItem>
                      <SelectItem value="retired">Retired</SelectItem>
                      <SelectItem value="housewife">Housewife</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label :for="`member_${index}_monthly_income`" class="block mb-3 text-sm font-medium text-gray-700">Monthly Income (₱)</Label>
                  <Input
                    :id="`member_${index}_monthly_income`"
                    v-model.number="member.monthly_income"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Enter monthly income"
                    class="mb-2"
                  />
                </div>

                <!-- Status Checkboxes -->
                <div class="md:col-span-2 lg:col-span-3">
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center gap-2">
                      <Switch
                        :id="`member_${index}_is_working`"
                        v-model:checked="member.is_working"
                      />
                      <Label :for="`member_${index}_is_working`" class="text-sm">Working</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch
                        :id="`member_${index}_is_student`"
                        v-model:checked="member.is_student"
                      />
                      <Label :for="`member_${index}_is_student`" class="text-sm">Student</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch
                        :id="`member_${index}_is_senior_citizen`"
                        v-model:checked="member.is_senior_citizen"
                      />
                      <Label :for="`member_${index}_is_senior_citizen`" class="text-sm">Senior Citizen</Label>
                    </div>

                    <div class="flex items-center gap-2">
                      <Switch
                        :id="`member_${index}_is_pwd`"
                        v-model:checked="member.is_pwd"
                      />
                      <Label :for="`member_${index}_is_pwd`" class="text-sm">PWD</Label>
                    </div>
                  </div>

                  <div v-if="member.is_pwd" class="mt-4">
                    <Label :for="`member_${index}_disability_details`" class="block mb-3 text-sm font-medium text-gray-700">Disability Details</Label>
                    <Textarea
                      :id="`member_${index}_disability_details`"
                      v-model="member.disability_details"
                      placeholder="Describe the disability"
                      rows="2"
                      class="mb-2"
                    />
                  </div>
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                  <Label :for="`member_${index}_additional_notes`" class="block mb-3 text-sm font-medium text-gray-700">Additional Notes</Label>
                  <Textarea
                    :id="`member_${index}_additional_notes`"
                    v-model="member.additional_notes"
                    placeholder="Any additional information about this family member"
                    rows="2"
                    class="mb-2"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Notes Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <FileText class="w-5 h-5 text-yellow-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Additional Notes</h3>
          </div>

          <div>
            <Label for="additional_notes" class="block mb-3 text-sm font-medium text-gray-700">Additional Information</Label>
            <Textarea
              id="additional_notes"
              v-model="form.additional_notes"
              placeholder="Any additional information about your household"
              rows="4"
              class="mb-2"
              :class="{ 'border-red-500': form.errors.additional_notes }"
            />
            <p v-if="form.errors.additional_notes" class="text-red-500 text-sm mt-2 mb-4">
              {{ form.errors.additional_notes }}
            </p>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-6 border-t">
          <Button type="button" variant="outline" @click="goBack">
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            <Save v-else class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Creating...' : 'Create Household Profile' }}
          </Button>
        </div>
      </form>
    </div>
  </ResidentResponsiveLayout>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import ResidentResponsiveLayout from '@/components/resident/ResidentResponsiveLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
  Users,
  ArrowLeft,
  Home,
  DollarSign,
  Building,
  Car,
  Plus,
  Trash2,
  FileText,
  Save,
  Loader2,
} from 'lucide-vue-next';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

interface Props {
  user: any;
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
  { title: 'Dashboard', href: '/resident/dashboard' },
  { title: 'Household Profile', href: '/resident/household-profile' },
  { title: 'Create', href: '/resident/household-profile/create' },
]);

const form = useForm({
  household_head_name: '',
  household_head_relationship: 'self',
  monthly_income: null as number | null,
  income_source: '',
  income_source_details: '',
  total_family_members: 1,
  working_members: 0,
  dependent_members: 0,
  housing_type: '',
  housing_details: '',
  has_vehicle: false,
  vehicle_details: '',
  has_livestock: false,
  livestock_details: '',
  additional_notes: '',
  family_members: [] as any[],
});

const progressPercentage = computed(() => {
  let completed = 0;
  let total = 8; // Total number of sections

  // Household Information
  if (form.household_head_name) completed++;
  if (form.household_head_relationship) completed++;
  if (form.total_family_members > 0) completed++;

  // Income Information
  if (form.monthly_income !== null) completed++;

  // Housing Information
  if (form.housing_type) completed++;

  // Assets
  if (form.has_vehicle || form.has_livestock) completed++;

  // Family Members
  if (form.family_members.length > 0) completed++;

  // Additional Notes
  if (form.additional_notes) completed++;

  return (completed / total) * 100;
});

const addFamilyMember = () => {
  form.family_members.push({
    first_name: '',
    middle_name: '',
    last_name: '',
    relationship_to_head: '',
    birth_date: '',
    sex: '',
    civil_status: '',
    educational_attainment: '',
    occupation: '',
    employment_status: '',
    monthly_income: null,
    is_working: false,
    is_student: false,
    is_senior_citizen: false,
    is_pwd: false,
    disability_details: '',
    additional_notes: '',
  });
};

const removeFamilyMember = (index: number) => {
  form.family_members.splice(index, 1);
};

const submitForm = () => {
  form.post('/resident/household-profile', {
    onSuccess: () => {
      toast.success('Household profile created successfully!');
    },
    onError: () => {
      toast.error('Failed to create household profile. Please check the form and try again.');
    },
  });
};

const goBack = () => {
  router.visit('/resident/household-profile');
};
</script>
