<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Complete Profile',
        href: '/resident/onboarding',
    },
];

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  suffix: '',
  phone: '',
  birth_date: '',
  sex: '',
  civil_status: '',
  occupation: '',
  purok: '',
  photo: null as File | null,
})

const purokOptions = [
  'Purok 1','Purok 2','Purok 3','Purok 4','Purok 5','Purok 6','Purok 7','Purok 8','Purok 9','Purok 10'
]

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const uploadProgress = ref<number | null>(null)

type FileItem = { name: string; sizeKb: number; progress: number; uploaded: boolean }
const items = ref<FileItem[]>([])

const chooseFile = () => fileInput.value?.click()

const onFileSelected = (file: File | null) => {
  form.photo = file
  items.value = []
  uploadProgress.value = null
  if (file) {
    items.value.push({ name: file.name, sizeKb: Math.round(file.size / 1024), progress: 0, uploaded: false })
  }
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
    if (f && ['image/jpeg','image/png'].includes(f.type)) {
      onFileSelected(f)
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
    forceFormData: true,
    onProgress: (event) => {
      uploadProgress.value = event?.percentage ?? null
      if (items.value.length) {
        items.value[0].progress = uploadProgress.value ?? 0
        items.value[0].uploaded = (uploadProgress.value ?? 0) >= 100
      }
    },
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
            <option v-for="p in purokOptions" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-[#334155]">Profile photo (JPG/PNG, max 2MB)</label>
          <div
            class="flex cursor-pointer flex-col items-center justify-center rounded border-2 border-dashed px-6 py-8 text-center"
            :class="isDragging ? 'border-[#0EA5E9] bg-[#F0F9FF]' : 'border-slate-200'"
            @click="chooseFile"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mb-2 h-8 w-8 text-[#0EA5E9]"><path d="M12 16a1 1 0 0 1-1-1V7.41L8.7 9.7a1 1 0 1 1-1.4-1.42l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 1 1-1.4 1.42L13 7.41V15a1 1 0 0 1-1 1Z"/><path d="M5 15a1 1 0 0 0-1 1v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2a1 1 0 1 0-2 0v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1Z"/></svg>
            <div class="text-sm text-slate-600"><span class="text-[#0EA5E9]">Browse</span> file or drag & drop</div>
            <div class="text-xs text-slate-500">PNG or JPG up to 2MB</div>
            <input ref="fileInput" type="file" accept="image/png,image/jpeg" class="hidden" @change="onInputChange" />
          </div>
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


