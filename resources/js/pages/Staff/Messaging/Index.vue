<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { dispatchStaffMessagingUnreadCount } from '@/staffMessagingEvents';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
    Search,
    Plus,
    CheckCircle,
    Archive,
    MoreVertical,
    Send,
    ArrowLeft,
    Paperclip,
    Smile,
    Phone,
    Video,
    X,
    User,
    FileText,
    Image as LucideImage,
} from 'lucide-vue-next';
import { getPusher, isPusherAvailable } from '@/pusher';
import { messagingJsonFetch } from '@/utils/messagingHttp';
import { MESSAGING_QUICK_EMOJIS, postConversationMessage } from '@/utils/messagingAttachments';
import { mergeMessagingPendingAttachments } from '@/utils/messagingComposerUpload';
import { useMessagingInPageUpload } from '@/composables/useMessagingInPageUpload';
import { subscribeToConversationChannel, subscribeToStaffMessagingInboxChannel } from '@/composables/useMessagingPusher';
import { scheduleScrollToBottom } from '@/utils/scheduleScrollToBottom';

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
    };
    staff: {
        id: number;
        name: string;
        email: string;
    };
    latestMessages: Array<{
        id: number;
        content: string;
        created_at: string;
        sender: {
            id: number;
            name: string;
        };
    }>;
}

interface SearchableResident {
    id: number;
    name: string;
    email: string;
}

interface Props {
    conversations: {
        data: Conversation[];
        links: any[];
        meta: any;
    };
    unreadCount: number;
    search: string | null;
    currentUser: {
        id: number;
        name: string;
        email: string;
        role: string;
    };
}

const props = defineProps<Props>();

const page = usePage();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Messages', href: '/staff/messaging' },
]);

const searchQuery = ref(props.search || '');
const isLoading = ref(false);

const composeDialogOpen = ref(false);
const composeUserQuery = ref('');
const composeUsers = ref<SearchableResident[]>([]);
const composeUsersLoading = ref(false);
const composeSelectedResident = ref<SearchableResident | null>(null);
const composeFirstMessage = ref('');
const composeStarting = ref(false);
const composeError = ref<string | null>(null);
let composeSearchTimer: ReturnType<typeof setTimeout> | null = null;

const resetComposeState = (): void => {
    composeUserQuery.value = '';
    composeUsers.value = [];
    composeUsersLoading.value = false;
    composeSelectedResident.value = null;
    composeFirstMessage.value = '';
    composeError.value = null;
    composeStarting.value = false;
    if (composeSearchTimer !== null) {
        clearTimeout(composeSearchTimer);
        composeSearchTimer = null;
    }
};

const onComposeDialogOpenChange = (open: boolean): void => {
    composeDialogOpen.value = open;
    if (!open) {
        resetComposeState();
    }
};

const normalizeStaffConversationFromApi = (raw: Record<string, unknown>): Conversation => {
    const latest = (raw.latest_messages ?? raw.latestMessages) as Conversation['latestMessages'] | undefined;

    return {
        id: Number(raw.id),
        subject: (raw.subject as string | null) ?? null,
        last_message: (raw.last_message as string | null) ?? null,
        last_message_at: (raw.last_message_at as string | null) ?? null,
        resident_has_unread: Boolean(raw.resident_has_unread),
        staff_has_unread: Boolean(raw.staff_has_unread),
        resident: raw.resident as Conversation['resident'],
        staff: raw.staff as Conversation['staff'],
        latestMessages: Array.isArray(latest) ? latest : [],
    };
};

watch(composeUserQuery, (q) => {
    if (!composeDialogOpen.value) {
        return;
    }
    if (composeSearchTimer !== null) {
        clearTimeout(composeSearchTimer);
        composeSearchTimer = null;
    }
    const trimmed = q.trim();
    if (trimmed.length < 2) {
        composeUsers.value = [];
        composeUsersLoading.value = false;
        return;
    }
    composeUsersLoading.value = true;
    composeSearchTimer = setTimeout(async () => {
        composeSearchTimer = null;
        try {
            const params = new URLSearchParams({ q: trimmed });
            const response = await messagingJsonFetch(`/staff/messaging/search-users?${params.toString()}`, {
                method: 'GET',
            });
            if (response.ok) {
                const data = (await response.json()) as { users?: SearchableResident[] };
                composeUsers.value = data.users ?? [];
            } else {
                composeUsers.value = [];
            }
        } catch {
            composeUsers.value = [];
        } finally {
            composeUsersLoading.value = false;
        }
    }, 300);
});

const submitStartConversation = async (): Promise<void> => {
    if (!composeSelectedResident.value || composeStarting.value) {
        return;
    }
    composeStarting.value = true;
    composeError.value = null;
    try {
        const body: { resident_id: number; content?: string } = {
            resident_id: composeSelectedResident.value.id,
        };
        const trimmed = composeFirstMessage.value.trim();
        if (trimmed) {
            body.content = trimmed;
        }
        const response = await messagingJsonFetch('/staff/messaging/conversations/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const msg =
                (data.errors?.resident_id?.[0] as string | undefined) ||
                (data.errors?.conversation?.[0] as string | undefined) ||
                (data.message as string | undefined) ||
                'Could not start the conversation.';
            composeError.value = msg;
            return;
        }
        const rawConv = data.conversation as Record<string, unknown> | undefined;
        if (!rawConv) {
            composeError.value = 'Unexpected response from server.';
            return;
        }
        const conv = normalizeStaffConversationFromApi(rawConv);
        const list = props.conversations?.data;
        if (list) {
            const idx = list.findIndex((c) => c.id === conv.id);
            if (idx === -1) {
                list.unshift(conv);
            } else {
                Object.assign(list[idx], conv);
            }
        }
        onComposeDialogOpenChange(false);
        await selectConversation(conv);
    } catch {
        composeError.value = 'Something went wrong. Please try again.';
    } finally {
        composeStarting.value = false;
    }
};

// Conversation viewer state
const selectedConversation = ref<Conversation | null>(null);
const loadingMessages = ref(false);
const messages = ref<any[]>([]);
const newMessage = ref('');
const pendingAttachmentFiles = ref<File[]>([]);
const documentAttachmentInputRef = ref<HTMLInputElement | null>(null);
const imageAttachmentInputRef = ref<HTMLInputElement | null>(null);
const sendingMessage = ref(false);

const {
    isDraggingOver: isMessagingInPageDragOver,
    onDragOver: onMessagingInPageDragOver,
    onDragLeave: onMessagingInPageDragLeave,
    onDrop: onMessagingInPageDrop,
    onPaste: onMessagingInPagePaste,
} = useMessagingInPageUpload(pendingAttachmentFiles);
const typingTimeout = ref<number | null>(null);
const isTyping = ref(false);
const otherUserTyping = ref(false);
let unsubscribeConversation: (() => void) | null = null;
let unsubscribeUser: (() => void) | null = null;

const threadScrollRef = ref<HTMLElement | null>(null);
const threadEndRef = ref<HTMLElement | null>(null);

const scrollThreadToBottom = (): void => {
    scheduleScrollToBottom(threadScrollRef.value, threadEndRef.value, [60, 200, 400]);
};

const sidebarUnreadCount = ref(
    (typeof page.props.messagingUnreadCount === 'number' ? page.props.messagingUnreadCount : null) ??
        props.unreadCount ??
        0,
);

const syncSidebarUnreadFromRows = () => {
    const n = props.conversations?.data?.filter((c) => c.staff_has_unread).length ?? 0;
    sidebarUnreadCount.value = n;
    dispatchStaffMessagingUnreadCount(n);
};

watch(
    () => props.unreadCount,
    (c) => {
        if (typeof c === 'number') {
            sidebarUnreadCount.value = c;
        }
    },
);

watch(
    () => page.props.messagingUnreadCount,
    (c) => {
        if (typeof c === 'number') {
            sidebarUnreadCount.value = c;
        }
    },
);

const appendMessageIfNew = (msg: { id: number }) => {
    if (messages.value.some((m) => m.id === msg.id)) {
        return;
    }
    messages.value.push(msg);
    scrollThreadToBottom();
};

// Computed properties
const hasUnreadMessages = computed(() => sidebarUnreadCount.value > 0);

const canSendStaffMessage = computed(() => {
    return (
        !!selectedConversation.value &&
        !sendingMessage.value &&
        (newMessage.value.trim().length > 0 || pendingAttachmentFiles.value.length > 0)
    );
});

const isMessageFromCurrentUser = (message: { sender: { id: number } }): boolean => {
    return message.sender.id === props.currentUser.id;
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

const insertEmojiIntoNewMessage = (emoji: string): void => {
    newMessage.value += emoji;
    nextTick(() => document.getElementById('staff-messaging-new-message')?.focus());
};

const lastMessagePreviewFromSendResponse = (data: {
    message?: { content?: string; attachments?: { name?: string }[] };
    conversation?: { last_message?: string | null };
}): string => {
    if (data.conversation?.last_message) {
        return String(data.conversation.last_message);
    }
    const m = data.message;
    if (!m) {
        return '';
    }
    if (m.content?.trim()) {
        return m.content.trim();
    }
    if (m.attachments?.length) {
        return `📎 ${m.attachments[0]?.name ?? 'Attachment'}`;
    }

    return '';
};

const formatLastMessageTime = (dateString: string | null) => {
    if (!dateString) return 'No messages';

    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60);

    if (diffInHours < 1) {
        return 'Just now';
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)}h ago`;
    } else if (diffInHours < 48) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString();
    }
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const handleSearch = () => {
    isLoading.value = true;
    router.get('/staff/messaging',
        { search: searchQuery.value },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        }
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    router.get('/staff/messaging', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const archiveConversation = (conversationId: number) => {
    router.post(`/staff/messaging/conversations/${conversationId}/archive`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Conversation viewer functions
const selectConversation = async (conversation: Conversation) => {
    selectedConversation.value = conversation;
    loadingMessages.value = true;

    try {
        // Fetch messages for this conversation
        const response = await messagingJsonFetch(`/staff/messaging/conversations/${conversation.id}/json`, {
            method: 'GET',
        });

        if (response.ok) {
            const data = await response.json();
            messages.value = data.conversation.messages || [];

            const readResponse = await messagingJsonFetch(
                `/staff/messaging/conversations/${conversation.id}/mark-read`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: '{}',
                },
            );

            if (readResponse.ok) {
                const readData = await readResponse.json();
                const row = props.conversations?.data?.find((c) => c.id === conversation.id);
                if (row && readData.conversation) {
                    row.staff_has_unread = readData.conversation.staff_has_unread;
                } else if (row) {
                    row.staff_has_unread = false;
                }
                if (typeof readData.unread_total === 'number') {
                    sidebarUnreadCount.value = readData.unread_total;
                    dispatchStaffMessagingUnreadCount(readData.unread_total);
                } else {
                    syncSidebarUnreadFromRows();
                }
            }
        }
    } catch (error) {
        console.error('Error loading conversation:', error);
    } finally {
        loadingMessages.value = false;
        await nextTick();
        scrollThreadToBottom();
    }
};

const sendMessage = async () => {
    if (!canSendStaffMessage.value) {
        return;
    }

    sendingMessage.value = true;

    // Stop typing indicator
    stopTyping();

    try {
        const convId = selectedConversation.value!.id;
        const response = await postConversationMessage(`/staff/messaging/conversations/${convId}/messages`, {
            content: newMessage.value.trim(),
            files: [...pendingAttachmentFiles.value],
        });

        if (response.ok) {
            const data = await response.json();
            if (data.message) {
                appendMessageIfNew(data.message);
            }
            newMessage.value = '';
            pendingAttachmentFiles.value = [];

            const convIndex = props.conversations?.data?.findIndex((c) => c.id === selectedConversation.value?.id);
            if (convIndex !== undefined && convIndex >= 0 && props.conversations?.data) {
                const preview = lastMessagePreviewFromSendResponse(data);
                const row = props.conversations.data[convIndex];
                if (preview) {
                    row.last_message = preview;
                }
                if (data.message?.created_at) {
                    row.last_message_at = data.message.created_at;
                }
            }
        }
    } catch (error) {
        console.error('Error sending message:', error);
    } finally {
        sendingMessage.value = false;
    }
};

const closeConversation = () => {
    selectedConversation.value = null;
    messages.value = [];
    newMessage.value = '';
    pendingAttachmentFiles.value = [];
};

// Typing indicator functions
const handleTyping = () => {
    if (!selectedConversation.value || isTyping.value) return;

    isTyping.value = true;

    // Send typing start event
    messagingJsonFetch(`/staff/messaging/conversations/${selectedConversation.value.id}/typing/start`, {
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
    if (!selectedConversation.value || !isTyping.value) return;

    isTyping.value = false;

    // Send typing stop event
    messagingJsonFetch(`/staff/messaging/conversations/${selectedConversation.value.id}/typing/stop`, {
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

    if (!selectedConversation.value || !getPusher() || !isPusherAvailable()) {
        return;
    }

    const convId = selectedConversation.value.id;

    unsubscribeConversation = subscribeToConversationChannel(convId, {
        onMessageSent: (e) => {
            if (e.message.sender.id === props.currentUser?.id) {
                return;
            }
            appendMessageIfNew(e.message);

            const convIndex = props.conversations?.data?.findIndex((c) => c.id === e.conversation.id);
            if (convIndex !== undefined && convIndex >= 0 && props.conversations?.data) {
                const row = props.conversations.data[convIndex];
                row.last_message = e.conversation.last_message ?? row.last_message;
                row.last_message_at = e.conversation.last_message_at ?? row.last_message_at;
                row.staff_has_unread = e.conversation.staff_has_unread ?? row.staff_has_unread;
            }
            syncSidebarUnreadFromRows();
        },
        onUserTyping: (e) => {
            if (e.user.id === props.currentUser?.id) {
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

const setupUserChannel = () => {
    if (unsubscribeUser) {
        unsubscribeUser();
        unsubscribeUser = null;
    }
    if (!props.currentUser?.id || !getPusher() || !isPusherAvailable()) {
        return;
    }

    unsubscribeUser = subscribeToStaffMessagingInboxChannel({
        onMessageSent: async (e) => {
            if (e.message.sender.id === props.currentUser.id) {
                return;
            }
            const convId = e.conversation.id;
            const row = props.conversations?.data?.find((c) => c.id === convId);
            if (row) {
                row.last_message = e.conversation.last_message ?? row.last_message;
                row.last_message_at = e.conversation.last_message_at ?? row.last_message_at;
                if (!selectedConversation.value || selectedConversation.value.id !== convId) {
                    row.staff_has_unread = true;
                } else {
                    row.staff_has_unread = e.conversation.staff_has_unread ?? row.staff_has_unread;
                }
                syncSidebarUnreadFromRows();
            } else {
                try {
                    const r = await messagingJsonFetch('/staff/messaging/unread-count', { method: 'GET' });
                    if (r.ok) {
                        const d = await r.json();
                        const n = Number(d.count);
                        if (!Number.isNaN(n)) {
                            sidebarUnreadCount.value = n;
                            dispatchStaffMessagingUnreadCount(n);
                        }
                    }
                } catch {
                    // ignore
                }
            }
            const serverTotal = e.staff_messaging_unread_total;
            if (typeof serverTotal === 'number' && !Number.isNaN(serverTotal)) {
                sidebarUnreadCount.value = serverTotal;
                dispatchStaffMessagingUnreadCount(serverTotal);
            }
        },
    });
};

onMounted(() => {
    setupUserChannel();
});

watch(selectedConversation, () => {
    if (selectedConversation.value) {
        setupRealTimeMessaging();
    } else if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }
});

onBeforeUnmount(() => {
    if (unsubscribeConversation) {
        unsubscribeConversation();
        unsubscribeConversation = null;
    }
    if (unsubscribeUser) {
        unsubscribeUser();
        unsubscribeUser = null;
    }
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }
});
</script>

<template>
    <Head title="Messages" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-120px)] bg-gray-50 rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <!-- Left Sidebar: Conversations List -->
            <div class="w-full sm:w-80 bg-white border-r border-gray-200 flex flex-col" :class="selectedConversation ? 'hidden sm:flex' : 'flex'">
                <!-- Header -->
                <div class="p-4 border-b border-gray-200 bg-white">
                    <div class="flex items-center justify-between mb-3 gap-2">
                        <h1 class="text-lg font-semibold text-gray-900 shrink-0">Messages</h1>
                        <div class="flex items-center gap-2 shrink-0">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8 gap-1"
                                @click="onComposeDialogOpenChange(true)"
                            >
                                <Plus class="h-4 w-4" />
                                <span class="hidden sm:inline">New message</span>
                            </Button>
                            <div v-if="hasUnreadMessages" class="relative">
                                <Badge variant="destructive" class="text-xs px-2 py-1">
                                    {{ sidebarUnreadCount }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search conversations..."
                            class="pl-10 pr-4 h-9 rounded-full border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            @keyup.enter="handleSearch"
                        />
                        <Button
                            v-if="searchQuery"
                            variant="ghost"
                            size="sm"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 h-5 w-5 p-0 rounded-full"
                            aria-label="Clear search"
                            @click="clearSearch"
                        >
                            <X class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="!conversations?.data || conversations.data.length === 0" class="flex flex-col items-center justify-center h-full px-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <MessageSquare class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No conversations yet</h3>
                        <p class="text-gray-500 text-center text-sm">
                            {{ searchQuery ? 'No conversations match your search.' : 'Start chatting with residents to see messages here.' }}
                        </p>
                    </div>

                    <div v-else class="space-y-0.5 p-1">
                        <div
                            v-for="conversation in conversations.data"
                            :key="conversation.id"
                            class="group relative rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-50"
                            :class="{ 'bg-blue-50 border-l-2 border-blue-500': conversation.staff_has_unread }"
                        >
                            <div
                                @click="selectConversation(conversation)"
                                class="block p-3 rounded-lg cursor-pointer hover:bg-gray-100"
                                :class="selectedConversation?.id === conversation.id ? 'bg-blue-100 border border-blue-300' : ''"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- Avatar with Status -->
                                    <div class="relative flex-shrink-0">
                                        <Avatar class="h-9 w-9 ring-1 ring-white shadow-sm">
                                            <AvatarImage :src="`https://ui-avatars.com/api/?name=${conversation.resident.name}&background=3b82f6&color=fff&bold=true`" />
                                            <AvatarFallback class="font-semibold text-sm">{{ getInitials(conversation.resident.name) }}</AvatarFallback>
                                        </Avatar>
                                        <!-- Online indicator -->
                                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border border-white rounded-full"></div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <h3 class="font-medium text-gray-900 truncate text-xs">
                                                {{ conversation.resident.name }}
                                            </h3>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-gray-400">
                                                    {{ formatLastMessageTime(conversation.last_message_at) }}
                                                </span>
                                                <div v-if="conversation.staff_has_unread" class="flex-shrink-0">
                                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <p class="text-xs text-gray-500 line-clamp-1 leading-relaxed">
                                                {{ conversation.last_message || 'No messages yet' }}
                                            </p>
                                            <div class="ml-2 flex-shrink-0">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                                    @click.stop="archiveConversation(conversation.id)"
                                                >
                                                    <Archive class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Conversation Viewer -->
            <div class="flex-1 bg-white flex flex-col overflow-hidden" :class="selectedConversation ? 'flex' : 'hidden sm:flex'">
                <!-- Empty State -->
                <div v-if="!selectedConversation" class="flex items-center justify-center h-full bg-gray-50">
                    <div class="text-center max-w-sm">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <MessageSquare class="h-10 w-10 text-white" />
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Welcome to Messages</h2>
                        <p class="text-gray-600 mb-6">
                            Select a conversation from the sidebar to start chatting with residents.
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                Real-time messaging
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                Typing indicators
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                File attachments
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversation Chat -->
                <div v-else class="flex min-h-0 flex-col h-full">
                    <!-- Header - Fixed at Top -->
                    <div class="p-4 border-b border-gray-200 bg-white flex-shrink-0 sticky top-0 z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Button variant="ghost" size="sm" @click="closeConversation" class="p-2 sm:hidden">
                                    <ArrowLeft class="h-4 w-4" />
                                </Button>
                                <Avatar class="h-10 w-10">
                                    <AvatarImage :src="`https://ui-avatars.com/api/?name=${selectedConversation.resident.name}&background=10b981&color=fff&bold=true`" />
                                    <AvatarFallback>{{ getInitials(selectedConversation.resident.name) }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <h2 class="font-semibold text-gray-900">{{ selectedConversation.resident.name }}</h2>
                                    <p class="text-sm text-gray-500">{{ selectedConversation.resident.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="sm" class="p-2">
                                    <Phone class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" class="p-2">
                                    <Video class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" class="p-2">
                                    <MoreVertical class="h-4 w-4" />
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
                            class="pointer-events-none absolute inset-0 z-[5] flex items-center justify-center border-2 border-dashed border-blue-400 bg-blue-50/90"
                        >
                            <div class="rounded-lg bg-white px-4 py-3 text-center shadow-md">
                                <p class="text-sm font-medium text-blue-900">Drop files to attach</p>
                                <p class="mt-1 text-xs text-blue-700">PDF, Word, or images · up to 5 files</p>
                            </div>
                        </div>

                    <!-- Messages Area - Only This Scrolls -->
                    <div ref="threadScrollRef" class="min-h-0 flex-1 overflow-y-auto p-4 bg-gray-50">
                        <div v-if="loadingMessages" class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                <p class="text-sm text-gray-500">Loading conversation...</p>
                            </div>
                        </div>
                        <div v-else-if="messages.length === 0" class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <MessageSquare class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <p class="text-gray-500">No messages yet. Start the conversation!</p>
                            </div>
                        </div>
                        <div v-else class="space-y-3 pb-4">
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                class="flex mb-3 animate-in slide-in-from-bottom-2 duration-300"
                                :class="isMessageFromCurrentUser(message) ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm transition-all duration-200 hover:shadow-md"
                                    :class="isMessageFromCurrentUser(message)
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-br-md'
                                        : 'bg-white border border-gray-200 text-gray-900 rounded-bl-md'"
                                >
                                    <p v-if="message.content?.trim()" class="text-sm leading-relaxed break-words">
                                        {{ message.content }}
                                    </p>
                                    <MessageAttachmentList
                                        :attachments="message.attachments"
                                        :tone="isMessageFromCurrentUser(message) ? 'staff-outgoing' : 'incoming'"
                                    />
                                </div>
                            </div>

                            <!-- Professional Typing Indicator -->
                            <div v-if="otherUserTyping" class="flex justify-start">
                                <div class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl bg-gray-50 border border-gray-200 shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex space-x-1">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-1"></div>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-2"></div>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full typing-dot-3"></div>
                                        </div>
                                        <span class="text-xs text-gray-600 font-medium">{{ selectedConversation?.resident?.name }} is typing...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div ref="threadEndRef" class="h-px w-full shrink-0" aria-hidden="true" />
                    </div>

                    <!-- Message Input Panel - Fixed at Bottom -->
                    <div class="flex-shrink-0 border-t border-gray-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
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
                        <div class="flex items-end gap-3">
                            <div class="flex gap-1">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="p-2"
                                            aria-label="Attach file"
                                        >
                                            <Paperclip class="h-4 w-4" />
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
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="p-2"
                                            aria-label="Insert emoji"
                                        >
                                            <Smile class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start" class="w-64 p-2">
                                        <div class="grid grid-cols-6 gap-1">
                                            <button
                                                v-for="emoji in MESSAGING_QUICK_EMOJIS"
                                                :key="emoji"
                                                type="button"
                                                class="flex h-9 w-9 items-center justify-center rounded-md text-lg hover:bg-gray-100"
                                                @click="insertEmojiIntoNewMessage(emoji)"
                                            >
                                                {{ emoji }}
                                            </button>
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <Input
                                id="staff-messaging-new-message"
                                v-model="newMessage"
                                placeholder="Type a message..."
                                class="flex-1 rounded-full border-gray-300 px-4 py-2"
                                @keyup.enter="sendMessage"
                                @input="handleTyping"
                                @keydown.enter="stopTyping"
                                @paste="onMessagingInPagePaste"
                                :disabled="sendingMessage"
                            />
                            <Button
                                type="button"
                                :disabled="!canSendStaffMessage"
                                class="rounded-full h-10 w-10 p-0 bg-blue-600 hover:bg-blue-700"
                                @click="sendMessage"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </div>
                        <p class="mt-2 text-center text-xs text-gray-400">
                            Drop files on the chat or paste images from the clipboard while the message field is focused.
                        </p>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog :open="composeDialogOpen" @update:open="onComposeDialogOpenChange">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Message a resident</DialogTitle>
                    <DialogDescription>
                        Search by name or email, then optionally add a first message to open the conversation.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 pt-1">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="composeUserQuery"
                            placeholder="Search residents by name or email…"
                            class="pl-9"
                            autocomplete="off"
                        />
                    </div>
                    <div v-if="composeUsersLoading" class="flex items-center gap-2 text-sm text-gray-500 py-2">
                        <div class="h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                        Searching…
                    </div>
                    <ul
                        v-else-if="composeUsers.length > 0"
                        class="max-h-52 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100"
                    >
                        <li v-for="u in composeUsers" :key="u.id">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-gray-50"
                                :class="composeSelectedResident?.id === u.id ? 'bg-blue-50' : ''"
                                @click="composeSelectedResident = u"
                            >
                                <Avatar class="h-9 w-9 shrink-0">
                                    <AvatarImage
                                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&background=3b82f6&color=fff&bold=true`"
                                    />
                                    <AvatarFallback class="text-xs">{{ getInitials(u.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ u.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ u.email }}</p>
                                </div>
                            </button>
                        </li>
                    </ul>
                    <div
                        v-else-if="composeUserQuery.trim().length >= 2"
                        class="flex items-center gap-2 rounded-lg border border-dashed border-gray-200 bg-gray-50/80 px-3 py-6 text-sm text-gray-500"
                    >
                        <User class="h-5 w-5 shrink-0 text-gray-400" />
                        No residents match that search.
                    </div>
                    <p v-else class="text-sm text-gray-500">Type at least 2 characters to search.</p>

                    <div v-if="composeSelectedResident" class="space-y-2">
                        <p class="text-xs text-gray-600">
                            To:
                            <span class="font-medium text-gray-900">{{ composeSelectedResident.name }}</span>
                        </p>
                        <Textarea
                            v-model="composeFirstMessage"
                            placeholder="Optional first message…"
                            :rows="3"
                            class="resize-none"
                        />
                    </div>

                    <p v-if="composeError" class="text-sm text-red-600">{{ composeError }}</p>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="onComposeDialogOpenChange(false)">Cancel</Button>
                    <Button
                        type="button"
                        :disabled="!composeSelectedResident || composeStarting"
                        @click="submitStartConversation"
                    >
                        {{ composeStarting ? 'Opening…' : 'Open conversation' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </StaffLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
</style>
