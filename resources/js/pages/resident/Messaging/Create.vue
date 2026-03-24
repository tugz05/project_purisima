<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useMessagingInPageUpload } from '@/composables/useMessagingInPageUpload';
import { mergeMessagingPendingAttachments } from '@/utils/messagingComposerUpload';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    MessageSquare,
    Search,
    ArrowLeft,
    Send,
    Users,
    UserCheck,
    CheckCircle,
    Paperclip,
    Smile,
    FileText,
    Image as LucideImage,
    X,
} from 'lucide-vue-next';
import { MESSAGING_QUICK_EMOJIS } from '@/utils/messagingAttachments';

interface StaffMember {
    id: number;
    name: string;
    email: string;
}

interface Props {
    staffMembers: StaffMember[];
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Home', href: '/resident/dashboard' },
    { title: 'Messages', href: '/resident/messaging' },
    { title: 'New Conversation', href: '/resident/messaging/create' },
]);

const selectedStaffId = ref<number | null>(null);
const subject = ref('');
const content = ref('');
const isLoading = ref(false);
const searchQuery = ref('');
const pendingAttachmentFiles = ref<File[]>([]);
const documentAttachmentInputRef = ref<HTMLInputElement | null>(null);
const imageAttachmentInputRef = ref<HTMLInputElement | null>(null);

const {
    isDraggingOver: isMessagingInPageDragOver,
    onDragOver: onMessagingInPageDragOver,
    onDragLeave: onMessagingInPageDragLeave,
    onDrop: onMessagingInPageDrop,
    onPaste: onMessagingInPagePaste,
} = useMessagingInPageUpload(pendingAttachmentFiles);

const selectedStaff = computed(() =>
    props.staffMembers.find((user) => user.id === selectedStaffId.value),
);

const filteredStaffMembers = computed(() => {
    if (!searchQuery.value) {
        return props.staffMembers;
    }
    const q = searchQuery.value.toLowerCase();

    return props.staffMembers.filter(
        (user) =>
            user.name.toLowerCase().includes(q) || user.email.toLowerCase().includes(q),
    );
});

const canSubmit = computed(() => {
    return (
        Boolean(selectedStaffId.value) &&
        (content.value.trim().length > 0 || pendingAttachmentFiles.value.length > 0)
    );
});

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const selectStaff = (staffId: number) => {
    selectedStaffId.value = staffId;
};

const addPendingFilesFromList = (fileList: FileList | null, kind: 'image' | 'document'): void => {
    if (!fileList?.length) {
        return;
    }
    const picked = Array.from(fileList);
    const filtered =
        kind === 'image'
            ? picked.filter((f) => f.type.startsWith('image/'))
            : picked.filter(
                  (f) =>
                      f.type === 'application/pdf' ||
                      f.type === 'application/msword' ||
                      f.type ===
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
                      /\.(pdf|doc|docx)$/i.test(f.name),
              );
    pendingAttachmentFiles.value = mergeMessagingPendingAttachments(
        pendingAttachmentFiles.value,
        filtered,
        5,
    );
};

const onDocumentAttachmentChosen = (e: Event): void => {
    const input = e.target as HTMLInputElement;
    addPendingFilesFromList(input.files, 'document');
    input.value = '';
};

const onImageAttachmentChosen = (e: Event): void => {
    const input = e.target as HTMLInputElement;
    addPendingFilesFromList(input.files, 'image');
    input.value = '';
};

const removePendingAttachment = (index: number): void => {
    pendingAttachmentFiles.value = pendingAttachmentFiles.value.filter((_, i) => i !== index);
};

const insertEmojiIntoContent = (emoji: string): void => {
    content.value += emoji;
    nextTick(() => document.getElementById('resident-create-message-content')?.focus());
};

const createConversation = async (): Promise<void> => {
    if (!canSubmit.value || isLoading.value) {
        return;
    }

    isLoading.value = true;

    try {
        if (pendingAttachmentFiles.value.length === 0) {
            await router.post(
                '/resident/messaging',
                {
                    staff_id: selectedStaffId.value,
                    subject: subject.value.trim() || null,
                    content: content.value.trim(),
                },
                {
                    onFinish: () => {
                        isLoading.value = false;
                    },
                },
            );
        } else {
            const fd = new FormData();
            fd.append('staff_id', String(selectedStaffId.value));
            const subj = subject.value.trim();
            if (subj) {
                fd.append('subject', subj);
            }
            fd.append('content', content.value.trim());
            pendingAttachmentFiles.value.forEach((f) => fd.append('attachments[]', f));

            await router.post('/resident/messaging', fd, {
                forceFormData: true,
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        }
    } catch {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Start New Conversation" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="space-y-4">
                <Link
                    href="/resident/messaging"
                    class="mb-4 inline-flex items-center gap-2 text-gray-600 transition-colors hover:text-gray-900"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span class="text-sm font-medium">Back to Messages</span>
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Start New Conversation</h1>
                <p class="mt-2 text-gray-600">
                    Contact a staff member to get help with your concerns or questions.
                </p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Left Side: Staff Selection -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Select Staff Member
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-gray-400"
                                />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search staff members..."
                                    class="rounded-full border-gray-300 pl-10 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div class="max-h-80 space-y-3 overflow-y-auto">
                                <div
                                    v-if="filteredStaffMembers.length === 0"
                                    class="py-8 text-center text-gray-500"
                                >
                                    <p>No staff members found</p>
                                </div>

                                <div
                                    v-for="staff in filteredStaffMembers"
                                    :key="staff.id"
                                    :class="[
                                        'flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition-all duration-200',
                                        selectedStaffId === staff.id
                                            ? 'border-green-500 bg-green-50 shadow-md'
                                            : 'border-gray-200 hover:border-gray-300 hover:shadow-sm',
                                    ]"
                                    @click="selectStaff(staff.id)"
                                >
                                    <div class="relative">
                                        <Avatar class="h-12 w-12 shadow-sm ring-2 ring-white">
                                            <AvatarImage
                                                :src="`https://ui-avatars.com/api/?name=${staff.name}&background=10b981&color=fff&bold=true`"
                                            />
                                            <AvatarFallback class="font-semibold">{{
                                                getInitials(staff.name)
                                            }}</AvatarFallback>
                                        </Avatar>
                                        <div
                                            class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-blue-500"
                                        ></div>
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold text-gray-900">{{ staff.name }}</h3>
                                            <Badge variant="outline" class="text-xs">Staff</Badge>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ staff.email }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Available for conversation</p>
                                    </div>

                                    <div
                                        :class="[
                                            'h-5 w-5 rounded-full border-2 transition-colors',
                                            selectedStaffId === staff.id
                                                ? 'border-green-500 bg-green-500'
                                                : 'border-gray-300',
                                        ]"
                                    >
                                        <CheckCircle
                                            v-if="selectedStaffId === staff.id"
                                            class="h-full w-full p-0.5 text-white"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Side: Message Composition -->
                <div class="space-y-6">
                    <div v-if="selectedStaff" class="space-y-4">
                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-14 w-14 shadow-md ring-2 ring-white">
                                        <AvatarImage
                                            :src="`https://ui-avatars.com/api/?name=${selectedStaff.name}&background=10b981&color=fff&bold=true`"
                                        />
                                        <AvatarFallback class="text-lg font-bold">{{
                                            getInitials(selectedStaff.name)
                                        }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ selectedStaff.name }}</h3>
                                        <p class="text-gray-600">{{ selectedStaff.email }}</p>
                                        <Badge variant="outline" class="mt-1 text-xs">Barangay Staff</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                Compose Your Message
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <input
                                ref="documentAttachmentInputRef"
                                type="file"
                                class="hidden"
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                multiple
                                @change="onDocumentAttachmentChosen"
                            />
                            <input
                                ref="imageAttachmentInputRef"
                                type="file"
                                class="hidden"
                                accept="image/jpeg,image/png,image/jpg"
                                multiple
                                @change="onImageAttachmentChosen"
                            />

                            <div
                                class="relative rounded-lg border border-transparent p-1 transition-colors"
                                :class="{
                                    'border-green-500 bg-green-50/50': isMessagingInPageDragOver,
                                }"
                                @dragover="onMessagingInPageDragOver"
                                @dragleave="onMessagingInPageDragLeave"
                                @drop="onMessagingInPageDrop"
                            >
                                <div
                                    v-if="isMessagingInPageDragOver"
                                    class="pointer-events-none absolute inset-0 z-[2] flex items-center justify-center rounded-md border-2 border-dashed border-green-500 bg-green-50/90"
                                >
                                    <p class="text-sm font-medium text-green-900">Drop files to attach</p>
                                </div>

                                <div>
                                    <Label for="subject" class="text-sm font-medium">Subject (optional)</Label>
                                    <Input
                                        id="subject"
                                        v-model="subject"
                                        placeholder="Brief description of your concern..."
                                        class="mt-1"
                                    />
                                </div>

                                <div class="mt-4">
                                    <Label for="resident-create-message-content" class="text-sm font-medium">
                                        Message — type text and/or attach files (PDF, Word, or photos)
                                    </Label>
                                    <div class="relative mt-1">
                                        <Textarea
                                            id="resident-create-message-content"
                                            v-model="content"
                                            placeholder="Write your message here, or use the paperclip to add files. You can also paste images or drop files on this area."
                                            rows="6"
                                            class="resize-none pr-24"
                                            maxlength="5000"
                                            @paste="onMessagingInPagePaste"
                                        />
                                        <div
                                            class="absolute bottom-2 right-2 flex items-center gap-0.5"
                                        >
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="h-8 w-8 p-0"
                                                        aria-label="Insert emoji"
                                                    >
                                                        <Smile class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" class="w-64 p-2">
                                                    <div class="grid grid-cols-6 gap-1">
                                                        <button
                                                            v-for="emoji in MESSAGING_QUICK_EMOJIS"
                                                            :key="emoji"
                                                            type="button"
                                                            class="flex h-9 w-9 items-center justify-center rounded-md text-lg hover:bg-gray-100"
                                                            @click="insertEmojiIntoContent(emoji)"
                                                        >
                                                            {{ emoji }}
                                                        </button>
                                                    </div>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="h-8 w-8 p-0"
                                                        aria-label="Attach file"
                                                    >
                                                        <Paperclip class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" class="w-52">
                                                    <DropdownMenuItem
                                                        class="cursor-pointer gap-2"
                                                        @click.prevent="documentAttachmentInputRef?.click()"
                                                    >
                                                        <FileText class="h-4 w-4" />
                                                        Attach file (PDF, Word)
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        class="cursor-pointer gap-2"
                                                        @click.prevent="imageAttachmentInputRef?.click()"
                                                    >
                                                        <LucideImage class="h-4 w-4" />
                                                        Upload a photo
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>
                                    <div class="mt-1 flex justify-between text-xs text-gray-500">
                                        <span>Up to 5 files · same types as in chat</span>
                                        <span>{{ content.length }}/5000</span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="pendingAttachmentFiles.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="(file, idx) in pendingAttachmentFiles"
                                    :key="`${file.name}-${idx}`"
                                    class="inline-flex max-w-full items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                >
                                    <span class="truncate">{{ file.name }}</span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded-full p-0.5 hover:bg-gray-200"
                                        aria-label="Remove attachment"
                                        @click="removePendingAttachment(idx)"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>

                            <div class="pt-2">
                                <Button
                                    type="button"
                                    :disabled="!canSubmit || isLoading"
                                    class="w-full bg-green-600 py-3 text-white hover:bg-green-700"
                                    size="lg"
                                    @click="createConversation"
                                >
                                    <div v-if="isLoading" class="flex items-center justify-center gap-2">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                        ></div>
                                        <span>Starting conversation...</span>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2">
                                        <Send class="h-4 w-4" />
                                        <span>{{
                                            selectedStaff
                                                ? `Send to ${selectedStaff.name}`
                                                : 'Select staff first'
                                        }}</span>
                                    </div>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card class="mt-8 border-blue-200 bg-blue-50">
                <CardContent class="p-6">
                    <div class="flex items-start gap-3">
                        <div
                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500"
                        >
                            <UserCheck class="h-4 w-4 text-white" />
                        </div>
                        <div>
                            <h4 class="mb-2 font-semibold text-blue-900">
                                Need help choosing a staff member?
                            </h4>
                            <ul class="list-disc space-y-1 pl-5 text-sm text-blue-800">
                                <li><strong>General inquiries:</strong> Any available staff member can help</li>
                                <li><strong>Transaction questions:</strong> Staff can process documents and payments</li>
                                <li><strong>Services:</strong> Staff can assist with requests</li>
                                <li>
                                    All staff members are trained to help with a variety of barangay concerns
                                </li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ResidentLayout>
</template>

<style scoped>
/* scoped styles if needed */
</style>
