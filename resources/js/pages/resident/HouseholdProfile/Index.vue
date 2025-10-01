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
              <h1 class="text-3xl font-bold mb-2">Household Profile</h1>
              <p class="text-blue-100 text-lg">Manage your family information and income details</p>
            </div>
          </div>

          <div v-if="householdProfile" class="flex items-center gap-3">
            <Badge variant="secondary" class="bg-green-100 text-green-800 px-4 py-2">
              <CheckCircle class="w-4 h-4 mr-2" />
              Profile Complete
            </Badge>
            <Button @click="editProfile" class="bg-white/20 hover:bg-white/30 text-white border-white/30">
              <Edit class="w-4 h-4 mr-2" />
              Edit Profile
            </Button>
          </div>
        </div>
      </div>

      <!-- No Profile State -->
      <div v-if="!householdProfile" class="text-center py-12">
        <div class="max-w-md mx-auto">
          <div class="p-6 bg-gray-100 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
            <Users class="w-12 h-12 text-gray-400" />
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No Household Profile</h3>
          <p class="text-gray-600 mb-6">Create your household profile to manage family information and income details.</p>
          <Button @click="createProfile" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700">
            <Plus class="w-4 h-4 mr-2" />
            Create Household Profile
          </Button>
        </div>
      </div>

      <!-- Profile Content -->
      <div v-else class="space-y-6">
        <!-- Household Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Total Members -->
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-200">
            <div class="flex items-center gap-3 mb-3">
              <div class="p-2 bg-blue-100 rounded-lg">
                <Users class="w-5 h-5 text-blue-600" />
              </div>
              <h3 class="font-semibold text-blue-900">Total Members</h3>
            </div>
            <p class="text-3xl font-bold text-blue-800">{{ householdProfile.total_family_members }}</p>
          </div>

          <!-- Working Members -->
          <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-xl border border-green-200">
            <div class="flex items-center gap-3 mb-3">
              <div class="p-2 bg-green-100 rounded-lg">
                <Briefcase class="w-5 h-5 text-green-600" />
              </div>
              <h3 class="font-semibold text-green-900">Working Members</h3>
            </div>
            <p class="text-3xl font-bold text-green-800">{{ householdProfile.working_members }}</p>
          </div>

          <!-- Monthly Income -->
          <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-200">
            <div class="flex items-center gap-3 mb-3">
              <div class="p-2 bg-purple-100 rounded-lg">
                <DollarSign class="w-5 h-5 text-purple-600" />
              </div>
              <h3 class="font-semibold text-purple-900">Monthly Income</h3>
            </div>
            <p class="text-3xl font-bold text-purple-800">₱{{ formatCurrency(householdProfile.total_monthly_income) }}</p>
          </div>

          <!-- Dependents -->
          <div class="bg-gradient-to-br from-orange-50 to-red-50 p-6 rounded-xl border border-orange-200">
            <div class="flex items-center gap-3 mb-3">
              <div class="p-2 bg-orange-100 rounded-lg">
                <Heart class="w-5 h-5 text-orange-600" />
              </div>
              <h3 class="font-semibold text-orange-900">Dependents</h3>
            </div>
            <p class="text-3xl font-bold text-orange-800">{{ householdProfile.dependent_members }}</p>
          </div>
        </div>

        <!-- Household Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Household Details -->
          <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-2 bg-blue-100 rounded-lg">
                <Home class="w-5 h-5 text-blue-600" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900">Household Information</h3>
            </div>

            <div class="space-y-4">
              <div>
                <Label class="text-sm font-medium text-gray-500 mb-2 block">Household Head</Label>
                <p class="text-gray-900 font-medium">{{ householdProfile.household_head_name }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ householdProfile.household_head_relationship }}</p>
              </div>

              <div v-if="householdProfile.income_source">
                <Label class="text-sm font-medium text-gray-500 mb-2 block">Income Source</Label>
                <p class="text-gray-900">{{ householdProfile.income_source }}</p>
                <p v-if="householdProfile.income_source_details" class="text-sm text-gray-600 mt-1">{{ householdProfile.income_source_details }}</p>
              </div>

              <div v-if="householdProfile.housing_type">
                <Label class="text-sm font-medium text-gray-500 mb-2 block">Housing Type</Label>
                <p class="text-gray-900">{{ householdProfile.housing_type }}</p>
                <p v-if="householdProfile.housing_details" class="text-sm text-gray-600 mt-1">{{ householdProfile.housing_details }}</p>
              </div>
            </div>
          </div>

          <!-- Assets Information -->
          <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-2 bg-green-100 rounded-lg">
                <Car class="w-5 h-5 text-green-600" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900">Assets & Properties</h3>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-700">Vehicle</span>
                <Badge :variant="householdProfile.has_vehicle ? 'default' : 'secondary'">
                  {{ householdProfile.has_vehicle ? 'Yes' : 'No' }}
                </Badge>
              </div>

              <div v-if="householdProfile.has_vehicle && householdProfile.vehicle_details" class="text-sm text-gray-600 mt-2">
                {{ householdProfile.vehicle_details }}
              </div>

              <div class="flex items-center justify-between">
                <span class="text-gray-700">Livestock</span>
                <Badge :variant="householdProfile.has_livestock ? 'default' : 'secondary'">
                  {{ householdProfile.has_livestock ? 'Yes' : 'No' }}
                </Badge>
              </div>

              <div v-if="householdProfile.has_livestock && householdProfile.livestock_details" class="text-sm text-gray-600 mt-2">
                {{ householdProfile.livestock_details }}
              </div>
            </div>
          </div>
        </div>

        <!-- Family Members -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-purple-100 rounded-lg">
                <Users class="w-5 h-5 text-purple-600" />
              </div>
              <h3 class="text-xl font-semibold text-gray-900">Family Members</h3>
            </div>
            <Badge variant="outline" class="text-sm">
              {{ householdProfile.family_members?.length || 0 }} members
            </Badge>
          </div>

          <div v-if="householdProfile.family_members && householdProfile.family_members.length > 0" class="space-y-4">
            <div
              v-for="member in householdProfile.family_members"
              :key="member.id"
              class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 hover:border-blue-300 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <User class="w-6 h-6 text-blue-600" />
                  </div>
                  <div>
                    <h4 class="font-semibold text-gray-900">{{ member.full_name }}</h4>
                    <p class="text-sm text-gray-600 mt-1">{{ member.relationship_display }}</p>
                    <div class="flex items-center gap-2 mt-2">
                      <Badge v-if="member.is_working" variant="default" class="text-xs">Working</Badge>
                      <Badge v-if="member.is_student" variant="secondary" class="text-xs">Student</Badge>
                      <Badge v-if="member.is_senior_citizen" variant="outline" class="text-xs">Senior Citizen</Badge>
                      <Badge v-if="member.is_pwd" variant="destructive" class="text-xs">PWD</Badge>
                    </div>
                  </div>
                </div>

                <div class="text-right">
                  <p v-if="member.age" class="text-sm text-gray-600">{{ member.age }} years old</p>
                  <p v-if="member.occupation" class="text-sm text-gray-600 mt-1">{{ member.occupation }}</p>
                  <p v-if="member.monthly_income" class="text-sm font-medium text-green-600 mt-1">
                    ₱{{ formatCurrency(member.monthly_income) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8 text-gray-500">
            <Users class="w-12 h-12 mx-auto mb-4 text-gray-300" />
            <p>No family members added yet.</p>
          </div>
        </div>

        <!-- Additional Notes -->
        <div v-if="householdProfile.additional_notes" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <FileText class="w-5 h-5 text-yellow-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900">Additional Notes</h3>
          </div>
          <p class="text-gray-700 leading-relaxed">{{ householdProfile.additional_notes }}</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t">
          <Button variant="outline" @click="deleteProfile" class="text-red-600 hover:text-red-700 hover:bg-red-50">
            <Trash2 class="w-4 h-4 mr-2" />
            Delete Profile
          </Button>
          <Button @click="editProfile" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700">
            <Edit class="w-4 h-4 mr-2" />
            Edit Profile
          </Button>
        </div>
      </div>
    </div>
  </ResidentResponsiveLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import ResidentResponsiveLayout from '@/components/resident/ResidentResponsiveLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
  Users,
  Plus,
  Edit,
  CheckCircle,
  Briefcase,
  DollarSign,
  Heart,
  Home,
  Car,
  User,
  FileText,
  Trash2,
} from 'lucide-vue-next';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

interface Props {
  householdProfile?: any;
  user: any;
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
  { title: 'Dashboard', href: '/resident/dashboard' },
  { title: 'Household Profile', href: '/resident/household-profile' },
]);

const createProfile = () => {
  router.visit('/resident/household-profile/create');
};

const editProfile = () => {
  if (props.householdProfile) {
    router.visit(`/resident/household-profile/${props.householdProfile.id}/edit`);
  }
};

const deleteProfile = () => {
  if (confirm('Are you sure you want to delete your household profile? This action cannot be undone.')) {
    router.delete(`/resident/household-profile/${props.householdProfile.id}`, {
      onSuccess: () => {
        toast.success('Household profile deleted successfully!');
      },
      onError: () => {
        toast.error('Failed to delete household profile. Please try again.');
      },
    });
  }
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};
</script>
