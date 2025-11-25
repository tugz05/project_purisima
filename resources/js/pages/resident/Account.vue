<script setup lang="ts">
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const page = usePage()
const user = page.props.auth?.user as any

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Account',
        href: '/resident/account',
    },
];

const form = useForm({
  first_name: user?.first_name || '',
  middle_name: user?.middle_name || '',
  last_name: user?.last_name || '',
  suffix: user?.suffix || '',
  phone: user?.phone || '',
  birth_date: user?.birth_date || '',
  sex: user?.sex || '',
  civil_status: user?.civil_status || '',
  occupation: user?.occupation || '',
  purok: user?.purok || '',
  photo: null as File | null,
})

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const uploadProgress = ref<number | null>(null)

const chooseFile = () => fileInput.value?.click()
const onInputChange = (e: Event) => {
  const t = e.target as HTMLInputElement
  form.photo = t.files && t.files[0] ? t.files[0] : null
}
const onDrop = (e: DragEvent) => {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
  if (e.dataTransfer?.files?.length) {
    const f = e.dataTransfer.files[0]
    if (['image/jpeg','image/png'].includes(f.type)) form.photo = f
  }
}
const onDragOver = (e: DragEvent) => { e.preventDefault(); isDragging.value = true }
const onDragLeave = (e: DragEvent) => { e.preventDefault(); isDragging.value = false }

const submit = () => {
  form.post('/resident/account', {
    forceFormData: true,
    onProgress: (e) => { uploadProgress.value = e?.percentage ?? null },
  })
}
</script>

<template>
  <Head title="Edit account" />
  <ResidentLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-teal-50">
      <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4">
      <div class="mx-auto max-w-4xl rounded-2xl bg-white p-8 shadow-xl border border-gray-100">
      <div class="mb-4 flex items-center gap-4">
        <img v-if="user?.photo_url" :src="user.photo_url" class="h-16 w-16 rounded-full object-cover" />
        <div>
          <h2 class="text-lg font-semibold">Edit account</h2>
          <Link href="/resident/dashboard" class="text-sm text-[#0EA5E9]">Back to dashboard</Link>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm">First name</label>
          <input v-model="form.first_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm">Middle name</label>
          <input v-model="form.middle_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm">Last name</label>
          <input v-model="form.last_name" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm">Suffix</label>
          <input v-model="form.suffix" class="w-full rounded border px-3 py-2" placeholder="Jr., Sr., II, III, etc." />
        </div>
        <div>
          <label class="mb-1 block text-sm">Phone</label>
          <input v-model="form.phone" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm">Birth date</label>
          <input type="date" v-model="form.birth_date" class="w-full rounded border px-3 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm">Sex</label>
          <select v-model="form.sex" class="w-full rounded border px-3 py-2">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm">Civil status</label>
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
          <label class="mb-1 block text-sm">Occupation</label>
          <input v-model="form.occupation" class="w-full rounded border px-3 py-2" />
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm">Purok</label>
          <input v-model="form.purok" class="w-full rounded border px-3 py-2" />
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm">Profile photo</label>
          <div
            class="flex cursor-pointer flex-col items-center justify-center rounded border-2 border-dashed px-6 py-8 text-center"
            :class="isDragging ? 'border-[#0EA5E9] bg-[#F0F9FF]' : 'border-slate-200'"
            @click="fileInput?.click()"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
          >
            <div class="text-sm text-slate-600"><span class="text-[#0EA5E9]">Browse</span> file or drag & drop</div>
            <div class="text-xs text-slate-500">PNG or JPG up to 2MB</div>
            <input ref="fileInput" type="file" accept="image/png,image/jpeg" class="hidden" @change="onInputChange" />
          </div>
          <div v-if="uploadProgress !== null" class="mt-2 h-2 w-full overflow-hidden rounded bg-slate-100">
            <div class="h-2 rounded bg-[#0EA5E9] transition-all" :style="{ width: uploadProgress + '%' }"></div>
          </div>
        </div>
      </div>

        <div class="mt-6">
          <button @click="submit" class="rounded bg-[#0EA5E9] px-4 py-2 text-white">Save changes</button>
        </div>
        </div>
      </div>
    </div>
  </ResidentLayout>
</template>

<style scoped>
</style>


