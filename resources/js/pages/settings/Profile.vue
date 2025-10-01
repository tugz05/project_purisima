<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user as any;

// Form for resident fields
const residentForm = useForm({
    first_name: user?.first_name || '',
    middle_name: user?.middle_name || '',
    last_name: user?.last_name || '',
    phone: user?.phone || '',
    birth_date: user?.birth_date || '',
    sex: user?.sex || '',
    civil_status: user?.civil_status || '',
    occupation: user?.occupation || '',
    purok: user?.purok || '',
    photo: null as File | null,
});

// Drag and drop state
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadProgress = ref<number | null>(null);

const chooseFile = () => fileInput.value?.click();

const onFileSelected = (file: File | null) => {
    residentForm.photo = file;
    uploadProgress.value = null;
};

const onInputChange = (e: Event) => {
    const t = e.target as HTMLInputElement;
    onFileSelected(t.files && t.files[0] ? t.files[0] : null);
};

const onDrop = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    isDragging.value = false;
    if (e.dataTransfer?.files?.length) {
        const f = e.dataTransfer.files[0];
        if (['image/jpeg','image/png'].includes(f.type)) {
            onFileSelected(f);
        }
    }
};

const onDragOver = (e: DragEvent) => { e.preventDefault(); isDragging.value = true; };
const onDragLeave = (e: DragEvent) => { e.preventDefault(); isDragging.value = false; };

const submitResidentForm = () => {
    residentForm.post('/profile', {
        forceFormData: true,
        onProgress: (e) => { uploadProgress.value = e?.percentage ?? null },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-teal-50">
                <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col space-y-8">
                <!-- Resident-specific fields -->
                <div v-if="user.role === 'resident'" class="space-y-6">
                    <HeadingSmall title="Resident Information" description="Update your resident profile details" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="first_name">First name</Label>
                            <Input
                                id="first_name"
                                v-model="residentForm.first_name"
                                class="mt-1 block w-full"
                                placeholder="First name"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.first_name" />
                        </div>

                        <div>
                            <Label for="middle_name">Middle name</Label>
                            <Input
                                id="middle_name"
                                v-model="residentForm.middle_name"
                                class="mt-1 block w-full"
                                placeholder="Middle name"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.middle_name" />
                        </div>

                        <div>
                            <Label for="last_name">Last name</Label>
                            <Input
                                id="last_name"
                                v-model="residentForm.last_name"
                                class="mt-1 block w-full"
                                placeholder="Last name"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.last_name" />
                        </div>

                        <div>
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                v-model="residentForm.phone"
                                class="mt-1 block w-full"
                                placeholder="Phone number"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.phone" />
                        </div>

                        <div>
                            <Label for="birth_date">Birth date</Label>
                            <Input
                                id="birth_date"
                                type="date"
                                v-model="residentForm.birth_date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.birth_date" />
                        </div>

                        <div>
                            <Label for="sex">Sex</Label>
                            <select
                                id="sex"
                                v-model="residentForm.sex"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                            >
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError class="mt-2" :message="residentForm.errors.sex" />
                        </div>

                        <div>
                            <Label for="civil_status">Civil status</Label>
                            <select
                                id="civil_status"
                                v-model="residentForm.civil_status"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                            >
                                <option value="">Select</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="separated">Separated</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError class="mt-2" :message="residentForm.errors.civil_status" />
                        </div>

                        <div class="sm:col-span-2">
                            <Label for="occupation">Occupation</Label>
                            <Input
                                id="occupation"
                                v-model="residentForm.occupation"
                                class="mt-1 block w-full"
                                placeholder="Occupation"
                            />
                            <InputError class="mt-2" :message="residentForm.errors.occupation" />
                        </div>

                        <div class="sm:col-span-2">
                            <Label for="purok">Purok</Label>
                            <Input
                                id="purok"
                                v-model="residentForm.purok"
                                class="mt-1 block w-full"
                                placeholder="Purok"
                                required
                            />
                            <InputError class="mt-2" :message="residentForm.errors.purok" />
                        </div>

                        <div class="sm:col-span-2">
                            <Label>Profile photo</Label>
                            <div class="mt-2 flex items-center gap-4">
                                <img v-if="user.photo_url" :src="user.photo_url" alt="Current photo" class="h-16 w-16 rounded-full object-cover" />
                                <div
                                    class="flex cursor-pointer flex-col items-center justify-center rounded border-2 border-dashed px-6 py-8 text-center"
                                    :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                                    @click="chooseFile"
                                    @dragover="onDragOver"
                                    @dragleave="onDragLeave"
                                    @drop="onDrop"
                                >
                                    <div class="text-sm text-gray-600"><span class="text-blue-600">Browse</span> file or drag & drop</div>
                                    <div class="text-xs text-gray-500">PNG or JPG up to 2MB</div>
                                    <input ref="fileInput" type="file" accept="image/png,image/jpeg" class="hidden" @change="onInputChange" />
                                </div>
                            </div>
                            <div v-if="uploadProgress !== null" class="mt-2 h-2 w-full overflow-hidden rounded bg-gray-200">
                                <div class="h-2 rounded bg-blue-500 transition-all" :style="{ width: uploadProgress + '%' }"></div>
                            </div>
                            <InputError class="mt-2" :message="residentForm.errors.photo" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button @click="submitResidentForm" :disabled="residentForm.processing">
                            {{ residentForm.processing ? 'Saving...' : 'Save Resident Info' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="residentForm.recentlySuccessful" class="text-sm text-neutral-600">Resident info saved.</p>
                        </Transition>
                    </div>
                </div>
                    </div>
                </div>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
