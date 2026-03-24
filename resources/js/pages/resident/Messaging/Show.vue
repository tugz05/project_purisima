<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { getPusher, isPusherAvailable } from '@/pusher';
import { messagingJsonFetch } from '@/utils/messagingHttp';
import { subscribeToConversationChannel } from '@/composables/useMessagingPusher';
import { playMessageSound } from '@/composables/useInAppAlertSounds';
import { scheduleScrollToBottom } from '@/utils/scheduleScrollToBottom';
import { userAvatarUrl } from '@/utils/userAvatar';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import MessageAttachmentList from '@/components/messaging/MessageAttachmentList.vue';
import {
    MessageSquare,
    Send,
    ArrowLeft,
    CheckCircle,
    CheckCircle2,
    Archive,
    Phone,
    Video,
    Smile,
    FileText,
    Image as LucideImage,
    Upload,
    X,
} from 'lucide-vue-next';
import { MESSAGING_QUICK_EMOJIS, postConversationMessage } from '@/utils/messagingAttachments';
import { mergeMessagingPendingAttachments } from '@/utils/messagingComposerUpload';
import { useMessagingInPageUpload } from '@/composables/useMessagingInPageUpload';

interface Message {
    id: number;
    content: string;
    type: string;
    attachments: any[] | null;
    is_read: boolean;
    is_edited: boolean;
    created_at: string;
    display_time: string;
    sender: {
        id: number;
        name: string;
        email: string;
        role?: string;
        photo_url?: string | null;
    };
}

interface Conversation {
    id: number;
    subject: string | null;
    last_message: string | null;
    last_message_at: string | null;
    resident_has_unread: boolean;
    staff_has_unread: boolean;
    resident: {
        id: number;
        name: string;
        email: string;
        photo_url?: string | null;
    };
    staff: {
        id: number;
        name: string;
        email: string;
        photo_url?: string | null;
    };
    messages: Message[];
}

interface Props {
    conversation: Conversation;
    unreadCount: number;
}

const props = defineProps<Props>();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user?: { id: number } })?.user?.id ?? 0);

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Home', href: '/resident/dashboard' },
    { title: 'Messages', href: '/resident/messaging' },
    { title: props.conversation.staff.name, href: `/resident/messaging/conversations/${props.conversation.id}` },
]);

// Reactive data
const messageContent = ref('');
const pendingAttachmentFiles = ref<File[]>([]);
const documentAttachmentInputRef = ref<HTMLInputElement | null>(null);
const imageAttachmentInputRef = ref<HTMLInputElement | null>(null);
const isTyping = ref(false);

const {
    isDraggingOver: isMessagingInPageDragOver,
    onDragOver: onMessagingInPageDragOver,
    onDragLeave: onMessagingInPageDragLeave,
    onDrop: onMessagingInPageDrop,
    onPaste: onMessagingInPagePaste,
} = useMessagingInPageUpload(pendingAttachmentFiles);
const otherUserTyping = ref(false);
const messages = ref<Message[]>([...props.conversation.messages]);
const messagesContainer = ref<HTMLElement>();
const messagesEndRef = ref<HTMLElement | null>(null);
const isSending = ref(false);
const typingTimeout = ref<number | null>(null);

let unsubscribeConversation: (() => void) | null = null;

// Computed properties
const otherUser = computed(() => props.conversation.staff);

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatMessageTime = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const isCurrentUser = (senderId: number) => {
    return senderId === authUserId.value;
};

const appendMessageIfNew = (msg: Message) => {
    if (messages.value.some((m) => m.id === msg.id)) {
        return;
    }
    messages.value.push(msg);
};

const scrollToBottom = () => {
    scheduleScrollToBottom(messagesContainer.value, messagesEndRef.value, [50, 200, 450]);
};

const canSendResidentMessage = computed(() => {
    return (
        !isSending.value &&
        (messageContent.value.trim().length > 0 || pendingAttachmentFiles.value.length > 0)
    );
});

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

const insertEmojiIntoMessage = (emoji: string): void => {
    messageContent.value += emoji;
    nextTick(() => document.getElementById('resident-messaging-input')?.focus());
};

const sendMessage = async () => {
    if (!canSendResidentMessage.value) {
        return;
    }

    isSending.value = true;

    try {
        const response = await postConversationMessage(
            `/resident/messaging/conversations/${props.conversation.id}/messages`,
            {
                content: messageContent.value.trim(),
                files: [...pendingAttachmentFiles.value],
            },
        );

        if (response.ok) {
            const data = await response.json();
            if (data.message) {
                appendMessageIfNew(data.message);
            }
            messageContent.value = '';
            pendingAttachmentFiles.value = [];
            scrollToBottom();
        }
    } catch {
        // ignore
    } finally {
        isSending.value = false;
    }
};

const handleTyping = () => {
    if (!isTyping.value) {
        isTyping.value = true;
        messagingJsonFetch(`/resident/messaging/conversations/${props.conversation.id}/typing/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: '{}',
        });
    }

    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    typingTimeout.value = setTimeout(() => {
        if (isTyping.value) {
            isTyping.value = false;
            messagingJsonFetch(`/resident/messaging/conversations/${props.conversation.id}/typing/stop`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: '{}',
            });
        }
    }, 1000);
};

const archiveConversation = () => {
    router.post(`/resident/messaging/conversations/${props.conversation.id}/archive`, {}, {
        onSuccess: () => {
            router.visit('/resident/messaging');
        }
    });
};

const bindRealtime = () => {
    if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }
    if (!getPusher() || !isPusherAvailable()) {
        return;
    }

    unsubscribeConversation = subscribeToConversationChannel(props.conversation.id, {
        onMessageSent: (e) => {
            if (e.message.sender.id === authUserId.value) {
                return;
            }
            playMessageSound(e.message.id);
            appendMessageIfNew(e.message as Message);
            scrollToBottom();
        },
        onUserTyping: (e) => {
            if (e.user.id === authUserId.value) {
                return;
            }
            otherUserTyping.value = e.is_typing;
            if (e.is_typing) {
                setTimeout(() => {
                    otherUserTyping.value = false;
                }, 3000);
            }
        },
    });
};

onMounted(() => {
    scrollToBottom();
    bindRealtime();
});

onBeforeUnmount(() => {
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }

    if (isTyping.value) {
        messagingJsonFetch(`/resident/messaging/conversations/${props.conversation.id}/typing/stop`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: '{}',
        });
    }
});
</script>

<template>
    <Head :title="`Messages - ${conversation.staff.name}`" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-screen bg-gray-50">
            <!-- Left Sidebar: Conversations List -->
            <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                <!-- Back Button -->
                <div class="p-4 border-b border-gray-200">
                    <Link href="/resident/messaging" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        <span class="text-sm font-medium">Back to Messages</span>
                    </Link>
                </div>

                <!-- All Conversations -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-2 space-y-1">
                        <!-- Current conversation (highlighted) -->
                        <div class="bg-green-50 border-l-4 border-green-500 rounded-r-xl p-3 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <Avatar class="h-10 w-10 ring-2 ring-green-500 ring-opacity-30">
                                        <AvatarImage :src="userAvatarUrl(conversation.staff.name, conversation.staff.photo_url, { background: '2563eb' })" />
                                        <AvatarFallback class="text-white bg-green-600">{{ getInitials(conversation.staff.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-500 border-2 border-white rounded-full"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-900">{{ conversation.staff.name }}</p>
                                        <Badge variant="outline" class="text-xs">Staff</Badge>
                                    </div>
                                    <p class="text-xs text-gray-600 truncate">Barangay Staff</p>
                                </div>
                            </div>
                        </div>

                        <!-- Placeholder for other conversations -->
                        <div class="opacity-50">
                            <div class="rounded-xl p-3 bg-gray-50 animate-pulse">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                                    <div class="flex-1">
                                        <div class="h-3 bg-gray-200 rounded mb-1"></div>
                                        <div class="h-2 bg-gray-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Chat Interface -->
            <div class="flex min-h-0 flex-1 flex-col bg-white">
                <!-- Chat Header -->
                <div class="border-b border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <Avatar class="h-12 w-12 ring-2 ring-white shadow-sm">
                                    <AvatarImage :src="userAvatarUrl(otherUser.name, otherUser.photo_url, { background: '10b981' })" />
                                    <AvatarFallback class="font-semibold">{{ getInitials(otherUser.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-500 border-2 border-white rounded-full"></div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-semibold text-gray-900">{{ otherUser.name }}</h2>
                                    <Badge variant="outline" class="text-xs">Staff</Badge>
                                </div>
                                <p class="text-sm text-gray-600">{{ otherUser.email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="sm">
                                <Phone class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm">
                                <Video class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm" @click="archiveConversation">
                                <Archive class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    class="relative flex min-h-0 flex-1 flex-col"
                    @dragover="onMessagingInPageDragOver"
                    @dragleave="onMessagingInPageDragLeave"
                    @drop="onMessagingInPageDrop"
                >
                    <div
                        v-if="isMessagingInPageDragOver"
                        class="pointer-events-none absolute inset-0 z-[5] flex items-center justify-center border-2 border-dashed border-green-500 bg-green-50/90"
                    >
                        <div class="rounded-lg bg-white px-4 py-3 text-center shadow-md">
                            <p class="text-sm font-medium text-green-900">Drop files to attach</p>
                            <p class="mt-1 text-xs text-green-800">PDF, Word, or images · up to 5 files</p>
                        </div>
                    </div>

                <!-- Messages -->
                <div
                    ref="messagesContainer"
                    class="min-h-0 flex-1 overflow-y-auto p-6 space-y-4"
                >
                    <div v-if="messages.length === 0" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <MessageSquare class="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                            <p class="text-gray-600">Start the conversation with {{ conversation.staff.name }}</p>
                        </div>
                    </div>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="isCurrentUser(message.sender.id) ? 'justify-end' : 'justify-start'"
                    >
                        <div class="flex items-start gap-2 max-w-sm lg:max-w-md">
                            <!-- Avatar for incoming messages -->
                            <Avatar
                                v-if="!isCurrentUser(message.sender.id)"
                                class="h-8 w-8 ring-2 ring-gray-100 shadow-sm"
                            >
                                <AvatarImage :src="userAvatarUrl(message.sender.name, message.sender.photo_url, { background: '10b981' })" />
                                <AvatarFallback class="text-xs font-semibold">{{ getInitials(message.sender.name) }}</AvatarFallback>
                            </Avatar>

                            <!-- Message -->
                            <div
                                class="rounded-2xl px-4 py-2 shadow-sm transition-all duration-200"
                                :class="isCurrentUser(message.sender.id)
                                    ? 'bg-gradient-to-r from-green-500 to-green-600 text-white'
                                    : 'bg-gray-100 text-gray-900'"
                            >
                                <p v-if="message.content?.trim()" class="text-sm leading-relaxed break-words">
                                    {{ message.content }}
                                </p>
                                <MessageAttachmentList
                                    :attachments="message.attachments"
                                    :tone="isCurrentUser(message.sender.id) ? 'resident-outgoing' : 'incoming'"
                                />

                                <div class="flex items-center justify-end mt-1 gap-1">
                                    <span class="text-xs opacity-75">
                                        {{ formatMessageTime(message.created_at) }}
                                    </span>
                                    <div v-if="isCurrentUser(message.sender.id)" class="flex items-center">
                                        <CheckCircle2 v-if="message.is_read" class="h-3 w-3 ml-1" />
                                        <CheckCircle v-else class="h-3 w-3 ml-1" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="otherUserTyping" class="flex justify-start">
                        <div class="flex items-start gap-2">
                            <Avatar class="h-8 w-8 ring-2 ring-gray-100 shadow-sm">
                                <AvatarImage :src="userAvatarUrl(otherUser.name, otherUser.photo_url, { background: '10b981' })" />
                                <AvatarFallback class="text-xs font-semibold">{{ getInitials(otherUser.name) }}</AvatarFallback>
                            </Avatar>
                            <div class="bg-gray-100 rounded-2xl px-4 py-3 shadow-sm">
                                <div class="flex items-center gap-1">
                                    <div class="flex space-x-1">
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div ref="messagesEndRef" class="h-px w-full shrink-0" aria-hidden="true" />
                </div>

                <!-- Message Input -->
                <div class="flex-shrink-0 border-t border-gray-200 p-4">
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
                        v-if="pendingAttachmentFiles.length > 0"
                        class="mb-2 flex flex-wrap gap-2"
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
                    <div class="flex items-end gap-2 sm:gap-3">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-10 shrink-0 gap-1.5 rounded-full border-green-200 px-3 text-green-800 hover:bg-green-50"
                                    aria-label="Upload file or photo"
                                >
                                    <Upload class="h-4 w-4 shrink-0" />
                                    <span class="hidden text-xs font-medium sm:inline">Upload</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-52">
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
                        <div class="min-w-0 flex-1">
                            <div class="relative">
                                <Textarea
                                    id="resident-messaging-input"
                                    v-model="messageContent"
                                    placeholder="Type a message..."
                                    rows="1"
                                    class="resize-none rounded-full border-gray-300 pr-14 focus:border-green-500 focus:ring-green-500"
                                    @input="handleTyping"
                                    @keydown.enter.prevent="sendMessage"
                                    @paste="onMessagingInPagePaste"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2">
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
                                                    @click="insertEmojiIntoMessage(emoji)"
                                                >
                                                    {{ emoji }}
                                                </button>
                                            </div>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                        <Button
                            type="button"
                            :disabled="!canSendResidentMessage"
                            class="rounded-full h-10 w-10 p-0"
                            :class="canSendResidentMessage ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300'"
                            @click="sendMessage"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                    </div>
                    <p class="mt-2 text-center text-xs text-gray-400">
                        Drop files on the chat or paste images while the message field is focused.
                    </p>
                </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>

<style scoped>
.animate-bounce {
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        transform: translate3d(0,0,0);
    }
    40%, 43% {
        transform: translate3d(0,-8px,0);
    }
    70% {
        transform: translate3d(0,-4px,0);
    }
    90% {
        transform: translate3d(0,-2px,0);
    }
}
</style>
