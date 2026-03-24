<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getPusher, isPusherAvailable } from '@/pusher';
import { messagingJsonFetch } from '@/utils/messagingHttp';
import { subscribeToConversationChannel, subscribeToUserMessagingChannel } from '@/composables/useMessagingPusher';
import { scheduleScrollToBottom } from '@/utils/scheduleScrollToBottom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import MessageAttachmentList from '@/components/messaging/MessageAttachmentList.vue';
import { postConversationMessage } from '@/utils/messagingAttachments';
import { mergeMessagingPendingAttachments } from '@/utils/messagingComposerUpload';
import {
    MessageCircle,
    Send,
    Minimize2,
    X,
    CheckCircle,
    CheckCircle2,
    Upload,
    FileText,
    Image as LucideImage,
} from 'lucide-vue-next';

interface Message {
    id: number;
    content: string;
    type?: string;
    attachments?: unknown[] | null;
    created_at: string;
    is_read: boolean;
    sender: {
        id: number;
        name: string;
    };
}

interface Conversation {
    id: number;
    staff_name: string;
    messages: Message[];
    unread_count: number;
    is_active: boolean;
}

interface Props {
    initialConversations?: Conversation[];
}

const props = withDefaults(defineProps<Props>(), {
    initialConversations: () => []
});

// State
const isOpen = ref(false);
const isMinimized = ref(false);
const conversations = ref<Conversation[]>(props.initialConversations || []);
const currentConversation = ref<Conversation | null>(null);
const messageContent = ref('');
const pendingAttachmentFiles = ref<File[]>([]);
const documentAttachmentInputRef = ref<HTMLInputElement | null>(null);
const imageAttachmentInputRef = ref<HTMLInputElement | null>(null);
const isSending = ref(false);
const isLoading = ref(false);
const typingTimeout = ref<number | null>(null);
const isTyping = ref(false);
const otherUserTyping = ref(false);
let unsubscribeConversation: (() => void) | null = null;
let unsubscribeUser: (() => void) | null = null;
/** Unread total when conversation list is empty but server has unread messages */
const bootstrapUnread = ref(0);
const messagesScrollRef = ref<HTMLElement | null>(null);
const messagesEndRef = ref<HTMLElement | null>(null);

const scrollMessagesToBottom = (): void => {
    scheduleScrollToBottom(messagesScrollRef.value, messagesEndRef.value, [80, 220, 520]);
};

// Utilities to safely add messages without duplicates
const addMessageIfNew = (incomingMessage: any) => {
    if (!currentConversation.value) return;
    const alreadyExists = currentConversation.value.messages.some(m => m.id === incomingMessage.id);
    if (alreadyExists) {
        return;
    }
    currentConversation.value.messages.push(incomingMessage);
    scrollMessagesToBottom();
};

// Computed
const currentUser = computed(() => {
    // Get user from page props or use auth composable
    const page = usePage();
    return page.props.auth?.user || { id: 1, name: 'Current User', role: 'resident' };
});

const canSendFloatingMessage = computed(() => {
    return (
        !isSending.value &&
        !isLoading.value &&
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

const unreadCount = computed(
    () =>
        bootstrapUnread.value +
        conversations.value.reduce((count, conv) => count + (conv.unread_count || 0), 0),
);

/** Compact label for small FAB badge (mobile-friendly). */
const unreadBadgeLabel = computed(() => {
    const n = unreadCount.value;
    if (n <= 0) {
        return '';
    }
    return n > 9 ? '9+' : String(n);
});

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

const openChat = async () => {
    isOpen.value = true;
    isMinimized.value = false;

    // Auto-create conversation with general staff if none exists
    if (conversations.value.length === 0) {
        await createGeneralConversation();
    } else {
        currentConversation.value = conversations.value[0];
    }

    if (conversations.value.length > 0) {
        currentConversation.value = conversations.value[0];
    }

    scrollMessagesToBottom();
};

const closeChat = () => {
    isOpen.value = false;
    currentConversation.value = null;
};

const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
};

const createGeneralConversation = async () => {
    if (isLoading.value) return;

    isLoading.value = true;

    try {
        const response = await messagingJsonFetch('/resident/messaging/conversations/create-general', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                content: messageContent.value || 'Hello, I need assistance.',
            }),
        });

        if (response.ok) {
            const data = await response.json();
            if (data.conversation) {
                currentConversation.value = data.conversation;
                conversations.value.unshift(data.conversation);
                messageContent.value = '';
                isLoading.value = false;
                bootstrapUnread.value = 0;
                setupRealTimeMessaging();
                scrollMessagesToBottom();
            } else {
                isLoading.value = false;
            }
        } else {
            let errorMessage = `Server error (${response.status})`;
            try {
                const responseText = await response.text();
                const errorData = responseText ? JSON.parse(responseText) : {};
                errorMessage = errorData.error || errorData.message || errorMessage;
            } catch {
                // use default errorMessage
            }
            isLoading.value = false;
            alert(`Failed to create conversation (${response.status}): ${errorMessage}`);
        }
    } catch {
        isLoading.value = false;
        alert('Failed to create conversation. Please check your connection.');
    }
};

const sendMessage = async () => {
    const text = messageContent.value.trim();
    const files = [...pendingAttachmentFiles.value];
    if ((!text && files.length === 0) || isSending.value) {
        return;
    }

    isSending.value = true;

    // Stop typing indicator
    stopTyping();

    try {
        // If no conversation exists, create one first
        if (!currentConversation.value) {
            await createGeneralConversation();
            if (!currentConversation.value) {
                isSending.value = false;
                return;
            }
        }

        // Double-check that conversation exists and has valid ID
        if (!currentConversation.value || !currentConversation.value.id) {
            isSending.value = false;
            return;
        }

        const response = await postConversationMessage(
            `/resident/messaging/conversations/${currentConversation.value.id}/messages`,
            { content: text, files },
        );

        if (response.ok) {
            const data = await response.json();
            messageContent.value = '';
            pendingAttachmentFiles.value = [];

            // Update conversation with new message
            if (currentConversation.value && data.message) {
                addMessageIfNew(data.message);
            }
        } else {
            let errorMessage = `Server error (${response.status})`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.error || errorData.message || errorMessage;
            } catch {
                // Response is not JSON
            }

            // If conversation doesn't exist, try to recreate it
            if (response.status === 404 && errorMessage.includes('No query results')) {
                currentConversation.value = null;
                await createGeneralConversation();
                if (currentConversation.value) {
                    return sendMessage();
                }
            }
            alert('Failed to send message: ' + errorMessage);
        }
    } catch {
        alert('Failed to send message. Please check your connection.');
    } finally {
        isSending.value = false;
    }
};

// Typing indicator functions
const handleTyping = () => {
    if (!currentConversation.value || isTyping.value) return;

    isTyping.value = true;

    // Send typing start event
    messagingJsonFetch(`/resident/messaging/conversations/${currentConversation.value.id}/typing/start`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: '{}',
    });

    // Clear existing timeout
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    // Set timeout to stop typing (reduced to 1.5 seconds for more responsive feel)
    typingTimeout.value = setTimeout(() => {
        stopTyping();
    }, 1500);
};

const stopTyping = () => {
    if (!currentConversation.value || !isTyping.value) return;

    isTyping.value = false;

    // Send typing stop event
    messagingJsonFetch(`/resident/messaging/conversations/${currentConversation.value.id}/typing/stop`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: '{}',
    });

    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
        typingTimeout.value = null;
    }
};

const setupRealTimeMessaging = () => {
    if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }

    if (!currentConversation.value || !getPusher() || !isPusherAvailable()) {
        setupPollingUpdates();
        return;
    }

    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }

    const convId = currentConversation.value.id;

    unsubscribeConversation = subscribeToConversationChannel(convId, {
        onMessageSent: (e) => {
            if (e.message.sender.id === currentUser.value?.id) {
                return;
            }
            addMessageIfNew(e.message);
            const targetConversationId = e.conversation?.id ?? convId;
            const row = conversations.value.find((c) => c.id === targetConversationId);
            if (row && e.conversation?.last_message != null) {
                (row as { last_message?: string }).last_message = e.conversation.last_message ?? undefined;
            }
        },
        onUserTyping: (e) => {
            if (e.user.id === currentUser.value?.id) {
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

const setupUserMessagingChannel = () => {
    if (unsubscribeUser) {
        unsubscribeUser();
        unsubscribeUser = null;
    }
    const uid = currentUser.value?.id;
    if (!uid || !getPusher() || !isPusherAvailable()) {
        return;
    }

    unsubscribeUser = subscribeToUserMessagingChannel(uid, {
        onMessageSent: (e) => {
            if (e.message.sender.id === uid) {
                return;
            }
            const cid = e.conversation.id;
            const activelyViewing =
                isOpen.value && !isMinimized.value && currentConversation.value?.id === cid;
            if (activelyViewing) {
                return;
            }
            const conv = conversations.value.find((c) => c.id === cid);
            if (conv) {
                conv.unread_count = (conv.unread_count || 0) + 1;
                if (e.conversation.last_message != null) {
                    (conv as { last_message?: string }).last_message = e.conversation.last_message ?? undefined;
                }
            } else {
                bootstrapUnread.value += 1;
            }
        },
    });
};

// Polling-based real-time updates
let pollingInterval: number | null = null;

const setupPollingUpdates = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }

    pollingInterval = setInterval(async () => {
        if (currentConversation.value) {
            try {
                const response = await messagingJsonFetch(
                    `/resident/messaging/conversations/${currentConversation.value.id}/json`,
                    { method: 'GET' },
                );

                if (response.ok) {
                    const data = await response.json();
                    if (data.conversation && data.conversation.messages) {
                        const fetchedMessages = data.conversation.messages as any[];
                        const existingIds = new Set(currentConversation.value.messages.map(m => m.id));
                        const deduped = fetchedMessages.filter(m => !existingIds.has(m.id));
                        if (deduped.length > 0) {
                            currentConversation.value.messages.push(...deduped);
                            scrollMessagesToBottom();
                        }
                    }
                }
            } catch {
                // Polling error - retry on next interval
            }
        }
    }, 8000);
};

onMounted(async () => {
    try {
        const r = await messagingJsonFetch('/resident/messaging/unread-count', { method: 'GET' });
        if (r.ok) {
            const d = await r.json();
            const n = Number(d.count);
            if (!Number.isNaN(n) && n > 0) {
                bootstrapUnread.value = n;
            }
        }
    } catch {
        // ignore
    }

    setupUserMessagingChannel();

    if (props.initialConversations && props.initialConversations.length > 0) {
        conversations.value = props.initialConversations;
        bootstrapUnread.value = 0;
        if (props.initialConversations[0]) {
            currentConversation.value = props.initialConversations[0];
            setupRealTimeMessaging();
        }
    }
});

watch(currentConversation, () => {
    if (currentConversation.value) {
        setupRealTimeMessaging();
    } else if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
        setupPollingUpdates();
    }
});

watch(isOpen, (open) => {
    if (open && !isMinimized.value) {
        scrollMessagesToBottom();
    }
});

watch(isMinimized, (minimized) => {
    if (!minimized && isOpen.value) {
        scrollMessagesToBottom();
    }
});

watch(isLoading, (loading) => {
    if (!loading && isOpen.value && !isMinimized.value) {
        scrollMessagesToBottom();
    }
});

onBeforeUnmount(() => {
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }
    if (unsubscribeUser) {
        unsubscribeUser();
        unsubscribeUser = null;
    }

    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
});
</script>

<template>
    <!-- Floating Action Button: safe-area + clearance above mobile bottom nav -->
    <div
        v-if="!isOpen"
        class="fixed z-[9998] right-4 max-[767px]:right-[max(1rem,env(safe-area-inset-right,0px))] bottom-[calc(5rem+env(safe-area-inset-bottom,0px))] md:right-6 md:bottom-6 md:top-auto"
        :class="unreadCount > 0 ? 'md:right-24' : ''"
    >
        <Button
            @click="openChat"
            :aria-label="
                unreadCount > 0
                    ? `Open support chat, ${unreadCount} unread message${unreadCount === 1 ? '' : 's'}`
                    : 'Open support chat'
            "
            :class="[
                'relative h-14 w-14 shrink-0 overflow-visible rounded-full bg-blue-600 shadow-lg transition-all duration-300 hover:bg-blue-700 hover:shadow-xl md:h-16 md:w-16 transform hover:scale-110 active:scale-105',
                unreadCount > 0 ? '' : 'animate-pulse-glow',
            ]"
        >
            <span class="relative flex h-full w-full items-center justify-center">
                <MessageCircle class="h-7 w-7 shrink-0 md:h-8 md:w-8" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="destructive"
                    aria-hidden="true"
                    class="pointer-events-none absolute right-0 top-0 z-10 flex min-h-[1.125rem] min-w-[1.125rem] max-w-[2.5rem] translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white px-1.5 py-0.5 text-[10px] font-bold leading-none tabular-nums shadow-md md:min-h-6 md:min-w-6 md:px-2 md:text-xs"
                >
                    {{ unreadBadgeLabel }}
                </Badge>
            </span>
        </Button>
    </div>

    <!-- Mobile: dim page behind chat for focus + tap outside to close -->
    <div
        v-if="isOpen"
        class="fixed inset-0 z-[9997] bg-slate-900/40 backdrop-blur-[2px] md:hidden"
        aria-hidden="true"
        @click="closeChat"
    />

    <!-- Chat Window -->
    <Transition
        name="chatbot-genie"
        enter-active-class="duration-500 ease-out"
        leave-active-class="duration-300 ease-in"
        enter-from-class="opacity-0 max-md:translate-y-6 max-md:scale-95 md:translate-x-full md:translate-y-full md:scale-50"
        enter-to-class="opacity-100 translate-x-0 translate-y-0 scale-100"
        leave-from-class="opacity-100 translate-x-0 translate-y-0 scale-100"
        leave-to-class="opacity-0 max-md:translate-y-6 max-md:scale-95 md:translate-x-full md:translate-y-full md:scale-50"
    >
        <div
            v-if="isOpen"
            class="fixed z-[9999] flex min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-300 bg-white shadow-2xl transform-gpu max-md:left-3 max-md:right-3 max-md:w-auto md:left-auto md:rounded-lg md:shadow-2xl"
            :class="[
                isMinimized
                    ? 'h-14 md:w-80'
                    : 'max-md:h-[min(72dvh,calc(100svh-9.5rem))] md:h-[560px] md:w-[400px]',
                'max-md:bottom-[calc(5rem+env(safe-area-inset-bottom,0px))] md:bottom-6 md:right-6',
            ]"
            style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); transform-origin: bottom right;"
            @click.stop
        >
        <!-- Professional Header -->
        <div class="flex shrink-0 items-center justify-between rounded-t-2xl border-b border-slate-600 bg-slate-700 px-4 py-3 md:rounded-t-lg">
            <div v-if="!isMinimized" class="flex items-center gap-3">
                <div class="relative">
                    <Avatar class="h-8 w-8">
                        <AvatarImage
                            :src="currentConversation ? `https://ui-avatars.com/api/?name=${currentConversation.staff_name}&background=6366f1&color=fff&bold=true` : 'https://ui-avatars.com/api/?name=Staff&background=6366f1&color=fff&bold=true'"
                        />
                        <AvatarFallback class="bg-indigo-500 text-white text-xs font-medium">
                            {{ currentConversation ? getInitials(currentConversation.staff_name) : 'ST' }}
                        </AvatarFallback>
                    </Avatar>
                    <!-- Online Status Indicator -->
                    <div class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 bg-green-400 border-2 border-slate-700 rounded-full"></div>
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm leading-4">Barangay Support</h3>
                    <p class="flex items-center gap-1.5 text-xs leading-3 text-slate-300">
                        <span>Online</span>
                        <span class="text-slate-500" aria-hidden="true">|</span>
                        <span>Staff Team</span>
                    </p>
                </div>
            </div>
            <div v-else class="flex-1 ml-3">
                <h3 class="font-semibold text-white text-sm">Barangay Support</h3>
            </div>

            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="toggleMinimize"
                    class="text-slate-300 hover:text-white hover:bg-slate-600 p-1.5 h-8 w-8 rounded-md"
                >
                    <Minimize2 v-if="!isMinimized" class="h-4 w-4" />
                    <MessageCircle v-else class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="closeChat"
                    class="text-slate-300 hover:text-white hover:bg-slate-600 p-1.5 h-8 w-8 rounded-md"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Messages + composer: flex column so height works on mobile -->
        <div v-if="!isMinimized" class="flex min-h-0 min-w-0 flex-1 flex-col">
            <div
                ref="messagesScrollRef"
                class="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain bg-gray-50 px-4 py-3"
            >
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto mb-4 animate-spin"></div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Connecting to Support</h3>
                        <p class="text-gray-600 text-sm">Getting ready to help you...</p>
                    </div>
                </div>

                <!-- No conversation yet -->
                <div v-else-if="!currentConversation" class="flex flex-col items-center justify-center h-full">
                    <div class="text-center mb-8">
                        <MessageCircle class="h-16 w-16 text-blue-400 mx-auto mb-4" />
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Welcome to Support Chat</h3>
                        <p class="text-gray-600 text-sm max-w-xs">Send us a message below and our staff team will respond as soon as possible</p>
                    </div>
                </div>

                <!-- No messages yet -->
                <div v-else-if="currentConversation.messages.length === 0" class="flex flex-col items-center justify-center h-full">
                    <div class="text-center mb-8">
                        <MessageCircle class="h-16 w-16 text-blue-400 mx-auto mb-4" />
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Let's start chatting!</h3>
                        <p class="text-gray-600 text-sm max-w-xs">Type your message below to begin your conversation</p>
                    </div>
                </div>

                <div
                    v-else
                    v-for="message in currentConversation.messages"
                    :key="message.id"
                    class="flex mb-3 animate-in slide-in-from-bottom-2 duration-300"
                    :class="message.sender.id === currentUser.id ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[75%] px-4 py-3 rounded-2xl shadow-sm transition-all duration-200 hover:shadow-md"
                        :class="message.sender.id === currentUser.id
                            ? 'bg-gradient-to-r from-slate-600 to-slate-700 text-white rounded-br-md'
                            : 'bg-white text-slate-700 border border-gray-200 rounded-bl-md'"
                    >
                        <p v-if="message.content?.trim()" class="text-sm leading-relaxed">{{ message.content }}</p>
                        <MessageAttachmentList
                            :attachments="(message.attachments as any) ?? null"
                            :tone="message.sender.id === currentUser.id ? 'resident-outgoing' : 'incoming'"
                        />
                        <div class="flex items-center justify-end mt-1 gap-1">
                            <span :class="message.sender.id === currentUser.id ? 'text-xs text-slate-300' : 'text-xs text-gray-500'">
                                {{ formatMessageTime(message.created_at) }}
                            </span>
                            <div v-if="message.sender.id === currentUser.id" class="flex items-center">
                                <CheckCircle2 v-if="message.is_read" class="h-3 w-3 text-slate-300" />
                                <CheckCircle v-else class="h-3 w-3 text-slate-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Typing Indicator -->
                <div v-if="otherUserTyping" class="flex justify-start mb-2">
                    <div class="max-w-[75%] px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 shadow-sm">
                        <div class="flex items-center space-x-2">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-1"></div>
                                <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-2"></div>
                                <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-3"></div>
                            </div>
                            <span class="text-xs text-gray-600 font-medium">Staff is typing...</span>
                        </div>
                    </div>
                </div>

                <div ref="messagesEndRef" class="h-px w-full shrink-0" aria-hidden="true" />
            </div>

        <div
            class="shrink-0 w-full min-w-0 border-t border-gray-200 bg-white p-2 pb-[max(0.5rem,env(safe-area-inset-bottom,0px))] sm:p-3 sm:pb-3"
        >
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
                class="mb-2 flex max-h-20 flex-wrap gap-1.5 overflow-y-auto"
            >
                <span
                    v-for="(file, idx) in pendingAttachmentFiles"
                    :key="`${file.name}-${idx}`"
                    class="inline-flex max-w-full items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700"
                >
                    <span class="truncate">{{ file.name }}</span>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-0.5 hover:bg-slate-200"
                        aria-label="Remove attachment"
                        @click="removePendingAttachment(idx)"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </span>
            </div>
            <div class="flex w-full min-w-0 items-stretch gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-10 w-10 shrink-0 border-slate-300 p-0"
                            aria-label="Upload file or photo"
                            :disabled="isSending || isLoading"
                        >
                            <Upload class="h-4 w-4 text-slate-700" />
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
                    <Input
                        v-model="messageContent"
                        placeholder="Type a message..."
                        class="box-border h-10 w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white py-0 pl-3 pr-2 text-base leading-normal placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9 sm:text-sm"
                        @keydown.enter.prevent="sendMessage"
                        @input="handleTyping"
                        @keydown.enter="stopTyping"
                        :disabled="isSending || isLoading"
                    />
                </div>
                <Button
                    type="button"
                    aria-label="Send message"
                    @click="sendMessage"
                    :disabled="!canSendFloatingMessage || isSending || isLoading"
                    size="sm"
                    class="h-10 w-10 shrink-0 p-0 md:h-10 md:w-auto md:px-3 bg-slate-700 hover:bg-slate-800 disabled:bg-gray-300 transition-colors"
                >
                    <Send class="h-4 w-4 shrink-0 text-white" />
                </Button>
            </div>
        </div>
        </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Genie Effect Animations */
.chatbot-genie-enter-active,
.chatbot-genie-leave-active {
  transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.chatbot-genie-enter-from {
  opacity: 0;
  transform: scale(0.1) translateX(100%) translateY(100%) rotate(45deg);
}

.chatbot-genie-enter-to {
  opacity: 1;
  transform: scale(1) translateX(0) translateY(0) rotate(0deg);
}

.chatbot-genie-leave-from {
  opacity: 1;
  transform: scale(1) translateX(0) translateY(0) rotate(0deg);
}

.chatbot-genie-leave-to {
  opacity: 0;
  transform: scale(0.1) translateX(100%) translateY(100%) rotate(-45deg);
}

/* Scale and Pulse Effects for Button */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
  }
  50% {
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.8);
  }
}

/* Custom animations */
@keyframes slideInUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Professional typing indicator animation */
@keyframes typing-dot {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

.typing-dot-1 {
    animation: typing-dot 1.4s infinite ease-in-out;
    animation-delay: 0ms;
}

.typing-dot-2 {
    animation: typing-dot 1.4s infinite ease-in-out;
    animation-delay: 200ms;
}

.typing-dot-3 {
    animation: typing-dot 1.4s infinite ease-in-out;
    animation-delay: 400ms;
}

/* Message slide-in animation */
@keyframes slideInFromBottom {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in.slide-in-from-bottom-2 {
    animation: slideInFromBottom 0.3s ease-out;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

