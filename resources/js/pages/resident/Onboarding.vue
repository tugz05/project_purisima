<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onUnmounted } from 'vue'
import { toast } from 'vue-sonner'
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { PUROK_OPTIONS } from '@/purokOptions';
import { photoStore } from '@/routes/resident/onboarding';

type ProfileDraft = {
  first_name?: string
  middle_name?: string
  last_name?: string
  suffix?: string
  phone?: string
  birth_date?: string
  sex?: string
  civil_status?: string
  occupation?: string
  purok?: string
}

const props = withDefaults(
  defineProps<{
    existingPhotoUrl?: string | null
    profileDraft?: ProfileDraft | null
  }>(),
  {
    existingPhotoUrl: null,
    profileDraft: null,
  },
);

const page = usePage();

/** Page prop from controller, with fallback to shared auth user (Google OAuth photo_url). */
const resolvedExistingPhotoUrl = computed((): string | null => {
  const fromProp = props.existingPhotoUrl?.trim();
  if (fromProp) {
    return fromProp;
  }
  const authUser = page.props.auth as { user?: { photo_url?: string | null } } | undefined;
  const fromAuth = authUser?.user?.photo_url?.trim();
  if (fromAuth) {
    return fromAuth;
  }
  return null;
});

const hasExistingAvatar = computed(() => Boolean(resolvedExistingPhotoUrl.value));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Complete Profile',
        href: '/resident/onboarding',
    },
];

const d = props.profileDraft

const form = useForm({
  first_name: d?.first_name ?? '',
  middle_name: d?.middle_name ?? '',
  last_name: d?.last_name ?? '',
  suffix: d?.suffix ?? '',
  phone: d?.phone ?? '',
  birth_date: d?.birth_date ?? '',
  sex: d?.sex ?? '',
  civil_status: d?.civil_status ?? '',
  occupation: d?.occupation ?? '',
  purok: d?.purok ?? '',
})

const purokOptions = PUROK_OPTIONS

const hasLegacyPurok = computed(() => {
  const p = form.purok
  return typeof p === 'string' && p.length > 0 && !PUROK_OPTIONS.includes(p)
})

/** Matches server rule max:5120 (kilobytes) in ResidentProfileRequest. */
const MAX_PROFILE_PHOTO_BYTES = 5 * 1024 * 1024
const MAX_PROFILE_PHOTO_LABEL = '5 MB'
const photoTooLargeMessage = `The photo must be ${MAX_PROFILE_PHOTO_LABEL} or smaller.`

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const uploadProgress = ref<number | null>(null)
const photoUploading = ref(false)
const uploadPreviewBlob = ref<string | null>(null)

type FileItem = { name: string; sizeKb: number; progress: number; uploaded: boolean }
const items = ref<FileItem[]>([])

const chooseFile = () => {
  if (photoUploading.value) {
    return
  }
  fileInput.value?.click()
}

const revokeUploadPreview = () => {
  if (uploadPreviewBlob.value) {
    URL.revokeObjectURL(uploadPreviewBlob.value)
    uploadPreviewBlob.value = null
  }
}

onUnmounted(() => {
  revokeUploadPreview()
})

/** Local blob while uploading, then saved URL from the server after redirect. */
const activePreviewSrc = computed((): string => {
  if (uploadPreviewBlob.value) {
    return uploadPreviewBlob.value
  }
  return resolvedExistingPhotoUrl.value ?? ''
})

const showProfilePreview = computed(() => activePreviewSrc.value.length > 0)

const onFileSelected = (file: File | null) => {
  form.clearErrors('photo')

  if (!file) {
    if (!photoUploading.value) {
      revokeUploadPreview()
      items.value = []
      uploadProgress.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    }
    return
  }

  if (photoUploading.value) {
    return
  }

  if (file.size > MAX_PROFILE_PHOTO_BYTES) {
    form.setError('photo', photoTooLargeMessage)
    if (fileInput.value) {
      fileInput.value.value = ''
    }
    return
  }

  revokeUploadPreview()
  uploadPreviewBlob.value = URL.createObjectURL(file)
  items.value = [{ name: file.name, sizeKb: Math.round(file.size / 1024), progress: 0, uploaded: false }]
  uploadProgress.value = 0
  photoUploading.value = true

  router.post(photoStore.url(), { photo: file }, {
    forceFormData: true,
    preserveScroll: true,
    onProgress: (progress) => {
      const pct = progress?.percentage ?? null
      uploadProgress.value = pct
      if (items.value.length) {
        items.value[0].progress = pct ?? 0
        items.value[0].uploaded = (pct ?? 0) >= 100
      }
    },
    onSuccess: () => {
      toast.success('Profile photo saved.')
      revokeUploadPreview()
      items.value = []
      uploadProgress.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
    onError: (errors) => {
      const raw = errors.photo
      const msg = Array.isArray(raw) ? raw[0] : raw
      form.setError(
        'photo',
        typeof msg === 'string' ? msg : 'Could not upload photo. Please try again.',
      )
      revokeUploadPreview()
      items.value = []
      uploadProgress.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
    onFinish: () => {
      photoUploading.value = false
    },
  })
}

const onInputChange = (e: Event) => {
  const t = e.target as HTMLInputElement
  onFileSelected(t.files && t.files[0] ? t.files[0] : null)
}

const onDrop = (e: DragEvent) => {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
    const f = e.dataTransfer.files[0]
    if (f && ['image/jpeg', 'image/png'].includes(f.type)) {
      onFileSelected(f)
    } else if (f) {
      form.setError('photo', 'Please use a JPG or PNG image.')
    }
  }
}

const onDragOver = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = true
}
const onDragLeave = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = false
}

const submit = () => {
  form.post('/resident/onboarding', {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Complete your profile" />
  <ResidentLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-[#F8FAFC] py-8">
      <div class="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow">
      <h1 class="mb-6 text-xl font-semibold text-[#0f172a]">Resident onboarding</h1>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-[#334155]">First name</label>
          <input v-model="form.first_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Middle name</label>
          <input v-model="form.middle_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Last name</label>
          <input v-model="form.last_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Suffix</label>
          <input v-model="form.suffix" class="w-full rounded border px-3 py-2" placeholder="Jr., Sr., II, III, etc." />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Phone</label>
          <input v-model="form.phone" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Birth date</label>
          <input type="date" v-model="form.birth_date" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Sex</label>
          <select v-model="form.sex" class="w-full rounded border px-3 py-2">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm text-[#334155]">Civil status</label>
          <select v-model="form.civil_status" class="w-full rounded border px-3 py-2">
            <option value="">Select</option>
            <option value="single">Single</option>
            <option value="married">Married</option>
            <option value="widowed">Widowed</option>
            <option value="separated">Separated</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-[#334155]">Occupation</label>
          <input v-model="form.occupation" class="w-full rounded border px-3 py-2" />
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-[#334155]">Purok</label>
          <select v-model="form.purok" class="w-full rounded border px-3 py-2">
            <option value="">Select Purok</option>
            <option v-if="hasLegacyPurok" :value="form.purok">{{ form.purok }} (current — choose a new purok)</option>
            <option v-for="p in purokOptions" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm font-medium text-[#334155]">Profile photo</label>
          <p v-if="hasExistingAvatar" class="mb-3 text-sm text-slate-600">
            Your account already has a profile picture (for example from Google). You can keep it or choose a new file below — it uploads immediately and replaces the current one.
          </p>
          <p v-else class="mb-3 text-sm text-slate-600">
            Upload a clear photo (JPG or PNG, max {{ MAX_PROFILE_PHOTO_LABEL }}). It saves as soon as you select a file. A photo is required unless you sign in with a provider that supplies one.
          </p>
          <div
            v-if="showProfilePreview"
            class="mb-4 flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50/90 p-4 sm:flex-row sm:items-center"
          >
            <img
              :src="activePreviewSrc"
              :alt="photoUploading || uploadPreviewBlob ? 'Photo upload preview' : 'Profile photo from your account'"
              class="mx-auto h-24 w-24 shrink-0 rounded-full object-cover ring-2 ring-emerald-500/25 sm:mx-0"
              loading="eager"
              decoding="async"
              referrerpolicy="no-referrer"
            />
            <div class="min-w-0 flex-1 text-center sm:text-left">
              <p v-if="photoUploading || uploadPreviewBlob" class="text-sm font-medium text-emerald-950">
                Uploading your photo…
              </p>
              <template v-else>
                <p class="text-sm font-medium text-emerald-950">Profile photo preview</p>
                <p class="mt-1 text-sm text-emerald-900/90">
                  This picture is already on your account (for example from Google sign-in). It will stay unless you upload a different photo below.
                </p>
              </template>
            </div>
          </div>
          <label class="mb-1 block text-xs text-slate-500">
            Choose a file (JPG/PNG, max {{ MAX_PROFILE_PHOTO_LABEL }}) — it uploads right away; optional if you already have a photo above
          </label>
          <div
            class="flex flex-col items-center justify-center rounded border-2 border-dashed px-6 py-8 text-center"
            :class="[
              isDragging ? 'border-[#0EA5E9] bg-[#F0F9FF]' : 'border-slate-200',
              photoUploading ? 'pointer-events-none cursor-wait opacity-60' : 'cursor-pointer',
            ]"
            @click="chooseFile"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mb-2 h-8 w-8 text-[#0EA5E9]"><path d="M12 16a1 1 0 0 1-1-1V7.41L8.7 9.7a1 1 0 1 1-1.4-1.42l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 1 1-1.4 1.42L13 7.41V15a1 1 0 0 1-1 1Z"/><path d="M5 15a1 1 0 0 0-1 1v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2a1 1 0 1 0-2 0v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1Z"/></svg>
            <div class="text-sm text-slate-600"><span class="text-[#0EA5E9]">Browse</span> file or drag & drop</div>
            <div class="text-xs text-slate-500">PNG or JPG up to {{ MAX_PROFILE_PHOTO_LABEL }}</div>
            <input
              ref="fileInput"
              type="file"
              accept="image/png,image/jpeg"
              class="hidden"
              @change="onInputChange"
            />
          </div>
          <p v-if="form.errors.photo" class="mt-2 text-sm text-red-600">{{ form.errors.photo }}</p>
          <div v-if="items.length" class="mt-4 space-y-2">
            <div v-for="it in items" :key="it.name" class="rounded border px-3 py-2">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-slate-800">{{ it.name }}</div>
                  <div class="text-xs text-slate-500">{{ it.sizeKb }} KB</div>
                </div>
                <div class="text-xs" :class="it.uploaded ? 'text-green-600' : 'text-slate-500'">
                  {{ it.uploaded ? 'Uploaded' : (it.progress ? 'Uploading' : 'Pending') }}
                </div>
              </div>
              <div class="mt-2 h-2 w-full overflow-hidden rounded bg-slate-100">
                <div class="h-2 rounded bg-[#0EA5E9] transition-all" :style="{ width: (it.progress || 0) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-6 flex items-center gap-3">
        <button @click="submit" class="rounded bg-[#0EA5E9] px-4 py-2 text-white">Save</button>
        <Link href="/dashboard" class="text-sm text-[#0369a1]">Skip for now</Link>
      </div>
      </div>
    </div>
  </ResidentLayout>
</template>

<style scoped>
</style>


