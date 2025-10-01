<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import {
    MessageSquare,
    Send,
    ArrowLeft,
    Paperclip,
    MoreVertical,
    Clock,
    CheckCircle,
    CheckCircle2,
    Archive,
    Download,
    Phone,
    Video,
    Smile
} from 'lucide-vue-next';

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
    };
    staff: {
        id: number;
        name: string;
        email: string;
    };
    messages: Message[];
}

interface Props {
    conversation: Conversation;
    unreadCount: number;
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Messages', href: '/staff/messaging' },
    { title: props.conversation.resident.name, href: `/staff/messaging/conversations/${props.conversation.id}` },
]);

// Reactive data
const messageContent = ref('');
const isTyping = ref(false);
const typingUsers = ref<any[]>([]);
const messagesContainer = ref<HTMLElement>();
const isSending = ref(false);
const typingTimeout = ref<number | null>(null);

// Computed properties
const currentUser = computed(() => props.conversation.staff);
const otherUser = computed(() => props.conversation.resident);

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
    return senderId === currentUser.value.id;
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!messageContent.value.trim() || isSending.value) return;

    isSending.value = true;

    try {
        await router.post(`/staff/messaging/conversations/${props.conversation.id}/messages`, {
            content: messageContent.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                messageContent.value = '';
                scrollToBottom();
            },
            onFinish: () => {
                isSending.value = false;
            }
        });
    } catch (error) {
        console.error('Failed to send message:', error);
        isSending.value = false;
    }
};

const handleTyping = () => {
    if (!isTyping.value) {
        isTyping.value = true;
        router.post(`/staff/messaging/conversations/${props.conversation.id}/typing/start`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    // Clear existing timeout
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    // Set new timeout to stop typing
    typingTimeout.value = setTimeout(() => {
        if (isTyping.value) {
            isTyping.value = false;
            router.post(`/staff/messaging/conversations/${props.conversation.id}/typing/stop`, {}, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    }, 1000);
};

const archiveConversation = () => {
    router.post(`/staff/messaging/conversations/${props.conversation.id}/archive`, {}, {
        onSuccess: () => {
            router.visit('/staff/messaging');
        }
    });
};

const downloadAttachment = (attachment: any) => {
    const link = document.createElement('a');
    link.href = `/storage/${attachment.path}`;
    link.download = attachment.name;
    link.click();
};

onMounted(() => {
    scrollToBottom();

    // Set up real-time listeners
    // This would be implemented with Laravel Echo
    // Echo.private(`conversation.${props.conversation.id}`)
    //     .listen('message.sent', (e) => {
    //         // Handle new message
    //     })
    //     .listen('user.typing', (e) => {
    //         // Handle typing indicators
    //     });
});

onBeforeUnmount(() => {
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    if (isTyping.value) {
        router.post(`/staff/messaging/conversations/${props.conversation.id}/typing/stop`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }
});
</script>

<template>
    <Head :title="`Messages - ${conversation.resident.name}`" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-screen bg-gray-50">
            <!-- Left Sidebar: Conversations List -->
            <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                <!-- Back Button -->
                <div class="p-4 border-b border-gray-200">
                    <Link href="/staff/messaging" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        <span class="text-sm font-medium">Back to Messages</span>
                    </Link>
                </div>

                <!-- All Conversations -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-2 space-y-1">
                        <!-- Current conversation (highlighted) -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-xl p-3 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <Avatar class="h-10 w-10 ring-2 ring-blue-500 ring-opacity-30">
                                        <AvatarImage :src="`https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/person.svg`" />
                                        <AvatarFallback class="text-white bg-blue-600">{{ getInitials(conversation.resident.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-900">{{ conversation.resident.name }}</p>
                                        <span class="text-xs text-gray-500">Active now</span>
                                    </div>
                                    <p class="text-xs text-gray-600 truncate">Status message goes here</p>
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
            <div class="flex-1 flex flex-col bg-white">
                <!-- Chat Header -->
                <div class="border-b border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <Avatar class="h-12 w-12 ring-2 ring-white shadow-sm">
                                    <AvatarImage :src="`https://ui-avatars.com/api/?name=${otherUser.name}&background=10b981&color=fff&bold=true`" />
                                    <AvatarFallback class="font-semibold">{{ getInitials(otherUser.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                            </div>

                            <div>
                                <h2 class="font-semibold text-gray-900">{{ otherUser.name }}</h2>
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

                <!-- Messages -->
                <div
                    ref="messagesContainer"
                    class="flex-1 overflow-y-auto p-6 space-y-4"
                >
                    <div v-if="conversation.messages.length === 0" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <MessageSquare class="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                            <p class="text-gray-600">Start the conversation with {{ conversation.resident.name }}</p>
                        </div>
                    </div>

                    <div
                        v-for="message in conversation.messages"
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
                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${message.sender.name}&background=10b981&color=fff&bold=true`" />
                                <AvatarFallback class="text-xs font-semibold">{{ getInitials(message.sender.name) }}</AvatarFallback>
                            </Avatar>

                            <!-- Message -->
                            <div
                                class="rounded-2xl px-4 py-2 shadow-sm transition-all duration-200"
                                :class="isCurrentUser(message.sender.id)
                                    ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-900'"
                            >
                                <p class="text-sm leading-relaxed">{{ message.content }}</p>

                                <!-- Attachments -->
                                <div v-if="message.attachments && message.attachments.length > 0" class="mt-2 space-y-2">
                                    <div
                                        v-for="attachment in message.attachments"
                                        :key="attachment.name"
                                        class="flex items-center gap-2 p-2 rounded-lg"
                                        :class="isCurrentUser(message.sender.id)
                                            ? 'bg-white bg-opacity-20'
                                            : 'bg-gray-200'"
                                    >
                                        <Paperclip class="h-4 w-4" />
                                        <span class="text-xs truncate">{{ attachment.name }}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0 ml-auto"
                                            @click="downloadAttachment(attachment)"
                                        >
                                            <Download class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>

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
                    <div v-if="typingUsers.length > 0" class="flex justify-start">
                        <div class="flex items-start gap-2">
                            <Avatar class="h-8 w-8 ring-2 ring-gray-100 shadow-sm">
                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${typingUsers[0].name}&background=10b981&color=fff&bold=true`" />
                                <AvatarFallback class="text-xs font-semibold">{{ getInitials(typingUsers[0].name) }}</AvatarFallback>
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
                </div>

                <!-- Message Input -->
                <div class="border-t border-gray-200 p-4">
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <div class="relative">
                                <Textarea
                                    v-model="messageContent"
                                    placeholder="Type a message..."
                                    rows="1"
                                    class="resize-none rounded-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 pr-24"
                                    @input="handleTyping"
                                    @keydown.enter.prevent="sendMessage"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Smile class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Paperclip class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <Button
                            @click="sendMessage"
                            :disabled="!messageContent.trim() || isSending"
                            class="rounded-full h-10 w-10 p-0"
                            :class="messageContent.trim() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300'"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
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
