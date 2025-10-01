<template>
  <StaffLayout :breadcrumbs="staffAnnouncementsBreadcrumbs">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      <!-- Attractive Header -->
      <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 shadow-2xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0">
          <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-600/20 to-purple-600/20"></div>
        </div>
        <div class="relative px-6 py-8">
          <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="p-4 bg-white/20 rounded-2xl backdrop-blur-sm shadow-lg">
                  <Edit class="w-8 h-8 text-white" />
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-white mb-2">Edit Announcement</h1>
                  <p class="text-blue-100 text-lg">Update your announcement information</p>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <!-- Back Button -->
                <Button
                  type="button"
                  variant="outline"
                  @click="goBack"
                  class="flex items-center gap-3 px-6 py-3 text-lg border-2 border-white/30 hover:border-white/50 bg-white/10 hover:bg-white/20 text-white backdrop-blur-sm transition-all duration-200"
                >
                  <ArrowLeft class="w-5 h-5" />
                  Back to Announcements
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content - Two Column Layout -->
      <div class="max-w-[1600px] mx-auto px-6 py-8">
        <form @submit.prevent="submitForm" class="space-y-8">
          <!-- Two Column Layout -->
          <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

            <!-- Left Column - Content Editor (2/3 width) -->
            <div class="xl:col-span-2 space-y-6">
              <!-- Title Section -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-200">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                      <FileText class="w-5 h-5 text-blue-600" />
                    </div>
                    <h2 class="text-xl font-bold text-blue-900">Announcement Title</h2>
                  </div>
                </div>
                <div class="p-6">
                  <Input
                    id="title"
                    v-model="form.title"
                    placeholder="Enter a compelling and descriptive title..."
                    class="w-full text-lg py-4 px-4 border-2 border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.title }"
                  />
                  <p v-if="form.errors.title" class="text-red-500 text-sm mt-2 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    {{ form.errors.title }}
                  </p>
                </div>
              </div>

              <!-- Content Editor - MAIN FOCUS -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-purple-200">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                      <Edit3 class="w-5 h-5 text-purple-600" />
                    </div>
                    <div>
                      <h2 class="text-xl font-bold text-purple-900">Content Editor</h2>
                      <p class="text-purple-700 text-sm">Your creative workspace - craft your announcement here</p>
                    </div>
                  </div>
                </div>
                <div class="p-6">
                  <div class="border-2 border-gray-200 rounded-xl overflow-hidden focus-within:border-purple-500 focus-within:ring-4 focus-within:ring-purple-500/20 transition-all duration-300"
                       :class="{
                         'border-red-500 ring-red-500': form.errors.content,
                         'border-purple-500 ring-purple-500/20': form.content.length > 0 && !form.errors.content
                       }">
                    <VueQuillEditor v-model="form.content" />
                  </div>
                  <p v-if="form.errors.content" class="text-red-500 text-sm mt-3 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    {{ form.errors.content }}
                  </p>
                  <p v-else-if="form.content.length > 0" class="text-green-600 text-sm mt-3 flex items-center gap-1">
                    <CheckCircle class="w-4 h-4" />
                    Great! Your content is ready
                  </p>
                </div>
              </div>
            </div>

            <!-- Right Column - Settings & Meta (1/3 width) -->
            <div class="space-y-6">
              <!-- Announcement Details -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-200">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                      <Settings class="w-5 h-5 text-blue-600" />
                    </div>
                    <h2 class="text-xl font-bold text-blue-900">Details</h2>
                  </div>
                </div>
                <div class="p-6 space-y-6">
                  <!-- Type -->
                  <div>
                    <Label for="type" class="text-sm font-semibold text-gray-700 mb-2 block">
                      Type *
                    </Label>
                    <Select v-model="form.type">
                      <SelectTrigger class="w-full py-3 border-2 border-gray-200 focus:border-blue-500">
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="general">📢 General</SelectItem>
                        <SelectItem value="urgent">🚨 Urgent</SelectItem>
                        <SelectItem value="event">📅 Event</SelectItem>
                        <SelectItem value="notice">📋 Notice</SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="form.errors.type" class="text-red-500 text-sm mt-1">
                      {{ form.errors.type }}
                    </p>
                  </div>

                  <!-- Priority -->
                  <div>
                    <Label for="priority" class="text-sm font-semibold text-gray-700 mb-2 block">
                      Priority *
                    </Label>
                    <Select v-model="form.priority">
                      <SelectTrigger class="w-full py-3 border-2 border-gray-200 focus:border-blue-500">
                        <SelectValue placeholder="Select priority" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="low">🟢 Low Priority</SelectItem>
                        <SelectItem value="normal">🟡 Normal Priority</SelectItem>
                        <SelectItem value="high">🟠 High Priority</SelectItem>
                        <SelectItem value="urgent">🔴 Urgent Priority</SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="form.errors.priority" class="text-red-500 text-sm mt-1">
                      {{ form.errors.priority }}
                    </p>
                  </div>

                  <!-- Author Name -->
                  <div>
                    <Label for="author_name" class="text-sm font-semibold text-gray-700 mb-2 block">
                      Author Name
                    </Label>
                    <Input
                      id="author_name"
                      v-model="form.author_name"
                      placeholder="Your name"
                      class="w-full py-3 border-2 border-gray-200 focus:border-blue-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Publication Settings -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 border-b border-green-200">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-lg">
                      <CheckCircle class="w-5 h-5 text-green-600" />
                    </div>
                    <h2 class="text-xl font-bold text-green-900">Publication</h2>
                  </div>
                </div>
                <div class="p-6 space-y-6">
                  <!-- Publish Toggle -->
                  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border-2 border-gray-200 hover:border-green-300 transition-all duration-200">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-green-100 rounded-lg">
                        <CheckCircle class="w-5 h-5 text-green-600" />
                      </div>
                      <div>
                        <Label for="is_published" class="text-sm font-semibold text-gray-800 cursor-pointer">
                          Publish Immediately
                        </Label>
                        <p class="text-gray-600 text-xs">Make visible to public</p>
                      </div>
                    </div>
                    <Switch
                      id="is_published"
                      v-model:checked="form.is_published"
                      class="data-[state=checked]:bg-green-600 scale-110"
                    />
                  </div>

                  <!-- Featured Toggle -->
                  <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border-2 border-gray-200 hover:border-yellow-300 transition-all duration-200">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-yellow-100 rounded-lg">
                        <Star class="w-5 h-5 text-yellow-600" />
                      </div>
                      <div>
                        <Label for="is_featured" class="text-sm font-semibold text-gray-800 cursor-pointer">
                          Feature This
                        </Label>
                        <p class="text-gray-600 text-xs">Highlight as important</p>
                      </div>
                    </div>
                    <Switch
                      id="is_featured"
                      v-model:checked="form.is_featured"
                      class="data-[state=checked]:bg-yellow-600 scale-110"
                    />
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6 space-y-4">
                <div class="flex flex-col gap-3">
                  <Button
                    type="submit"
                    :disabled="form.processing || !isFormValid"
                    class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 px-6 py-3 text-lg font-semibold w-full"
                  >
                    <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                    <Bell v-else class="w-5 h-5" />
                    {{ form.processing ? 'Updating...' : 'Update Announcement' }}
                  </Button>

                  <Button
                    type="button"
                    variant="outline"
                    @click="saveDraft"
                    :disabled="form.processing"
                    class="flex items-center justify-center gap-3 px-6 py-3 text-lg border-2 border-gray-200 hover:border-gray-300 transition-all duration-200 w-full"
                  >
                    <Save class="w-5 h-5" />
                    Save as Draft
                  </Button>
                </div>
              </div>
            </div>
          </div>

        </form>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
  Edit,
  CheckCircle,
  Loader2,
  ArrowLeft,
  Save,
  FileText,
  Edit3,
  Settings,
  Star,
  X,
  Bell,
} from 'lucide-vue-next';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import StaffLayout from '@/layouts/staff/Layout.vue';
import VueQuillEditor from '@/components/VueQuillEditor.vue';

// Props
const props = defineProps<{
  announcement: any;
}>();

// Breadcrumbs
const { staffAnnouncementsBreadcrumbs } = useBreadcrumbs();

// Form
const form = useForm({
  title: props.announcement.title,
  content: props.announcement.content,
  type: props.announcement.type,
  priority: props.announcement.priority,
  author_name: props.announcement.author_name || '',
  is_published: props.announcement.is_published,
  is_featured: props.announcement.is_featured,
});

// Form validation
const isFormValid = computed(() => {
  return form.title.trim().length > 0 &&
         form.content.trim().length > 0 &&
         form.type &&
         form.priority;
});

const submitForm = () => {
  form.put(`/staff/announcements/${props.announcement.id}`, {
    onSuccess: () => {
      toast.success('Announcement updated successfully!');
    },
    onError: () => {
      toast.error('Failed to update announcement');
    },
  });
};

const saveDraft = () => {
  form.is_published = false;
  form.put(`/staff/announcements/${props.announcement.id}`, {
    onSuccess: () => {
      toast.success('Draft saved successfully!');
    },
    onError: () => {
      toast.error('Failed to save draft');
    },
  });
};

const goBack = () => {
  router.visit('/staff/announcements');
};
</script>
