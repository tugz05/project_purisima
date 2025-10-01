<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    MessageSquare,
    Search,
    Plus,
    Clock,
    CheckCircle,
    CheckCircle2,
    Archive,
    MoreVertical,
    Send,
    ArrowLeft,
    Paperclip,
    Smile,
    Phone,
    Video
} from 'lucide-vue-next';

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

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Dashboard', href: '/staff/dashboard' },
    { title: 'Messages', href: '/staff/messaging' },
]);

const searchQuery = ref(props.search || '');
const isLoading = ref(false);

// Conversation viewer state
const selectedConversation = ref<Conversation | null>(null);
const loadingMessages = ref(false);
const messages = ref<any[]>([]);
const newMessage = ref('');
const sendingMessage = ref(false);
const typingTimeout = ref<number | null>(null);
const isTyping = ref(false);
const otherUserTyping = ref(false);
const currentChannel = ref<any>(null);

// Computed properties
const hasUnreadMessages = computed(() => props.unreadCount > 0);

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
        const response = await fetch(`/staff/messaging/conversations/${conversation.id}/json`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (response.ok) {
            const data = await response.json();
            messages.value = data.conversation.messages || [];

            // Mark messages as read
            await fetch(`/staff/messaging/conversations/${conversation.id}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });
        }
    } catch (error) {
        console.error('Error loading conversation:', error);
    } finally {
        loadingMessages.value = false;
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedConversation.value || sendingMessage.value) return;

    sendingMessage.value = true;

    // Stop typing indicator
    stopTyping();

    try {
        const response = await fetch(`/staff/messaging/conversations/${selectedConversation.value.id}/messages`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                content: newMessage.value.trim()
            })
        });

        if (response.ok) {
            const data = await response.json();
            console.log('Staff: Send message response:', data);
            // Add the new message to the existing messages
            if (data.message) {
                messages.value.push(data.message);
                console.log('Staff: Added message locally');
            }
            newMessage.value = '';

            // Update the conversation in the sidebar
            const convIndex = props.conversations?.data?.findIndex(c => c.id === selectedConversation.value?.id);
            if (convIndex !== undefined && props.conversations?.data) {
                props.conversations.data[convIndex].last_message = data.message.content;
                props.conversations.data[convIndex].last_message_at = data.message.created_at;
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
};

// Typing indicator functions
const handleTyping = () => {
    if (!selectedConversation.value || isTyping.value) return;

    isTyping.value = true;

    // Send typing start event
    fetch(`/staff/messaging/conversations/${selectedConversation.value.id}/typing/start`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
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
    fetch(`/staff/messaging/conversations/${selectedConversation.value.id}/typing/stop`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    });

    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
        typingTimeout.value = null;
    }
};

onMounted(() => {
    // Set up real-time updates for unread count
    // This would be implemented with Laravel Echo
});

// Real-time messaging setup
const setupRealTimeMessaging = () => {
    console.log('Staff: Setting up real-time messaging...');
    console.log('Staff: Selected conversation:', selectedConversation.value);
    console.log('Staff: Echo object:', window.Echo);

    // Clean up previous channel if exists
    if (currentChannel.value) {
        console.log('Staff: Leaving previous channel:', currentChannel.value);
        (window as any).Echo.leave(`conversation.${currentChannel.value}`);
        currentChannel.value = null;
    }

    if (selectedConversation.value) {
        const channelName = `conversation.${selectedConversation.value.id}`;
        console.log('Staff: Creating private channel:', channelName);

        const channel = (window as any).Echo.private(channelName);
        currentChannel.value = selectedConversation.value.id;

        console.log('Staff: Channel created:', channel);

        // Listen for new messages
        channel.listen('.message.sent', (e: any) => {
            console.log('Staff: Received real-time message:', e);

            // Only add message if it's not from current user (avoid duplicates)
            if (e.message.sender.id !== props.currentUser?.id) {
                messages.value.push(e.message);
                console.log('Staff: Added message to conversation');

                // Auto-scroll to bottom
                setTimeout(() => {
                    const messagesContainer = document.querySelector('.overflow-y-auto');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }, 100);
            }

            // Update conversation in sidebar
            const convIndex = props.conversations?.data?.findIndex(c => c.id === e.conversation.id);
            if (convIndex !== undefined && props.conversations?.data) {
                props.conversations.data[convIndex].last_message = e.conversation.last_message;
                props.conversations.data[convIndex].last_message_at = e.conversation.last_message_at;
                props.conversations.data[convIndex].staff_has_unread = e.conversation.staff_has_unread;
            }
        });

        // Listen for typing indicators
        channel.listen('.user.typing', (e: any) => {
            console.log('Staff: Received typing event:', e);
            // Handle typing indicators
            if (e.user.id !== props.currentUser?.id) {
                otherUserTyping.value = e.is_typing;

                // Auto-hide typing indicator after 3 seconds
                if (e.is_typing) {
                    setTimeout(() => {
                        otherUserTyping.value = false;
                    }, 3000);
                }
            }
        });

        console.log('Staff: Real-time messaging setup complete');
    } else {
        console.log('Staff: No conversation to set up real-time messaging');
    }
};

// Watch for conversation changes to set up real-time listeners
watch(selectedConversation, () => {
    if (selectedConversation.value) {
        setupRealTimeMessaging();
    }
});

onBeforeUnmount(() => {
    // Clean up Echo channel
    if (currentChannel.value) {
        (window as any).Echo.leave(`conversation.${currentChannel.value}`);
    }

    // Clean up typing timeout
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
                    <div class="flex items-center justify-between mb-3">
                        <h1 class="text-lg font-semibold text-gray-900">Messages</h1>
                        <div v-if="hasUnreadMessages" class="relative">
                            <Badge variant="destructive" class="text-xs px-2 py-1">
                                {{ unreadCount }}
                            </Badge>
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
                            @click="clearSearch"
                        >
                            ×
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
                <div v-else class="flex flex-col h-full">
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

                    <!-- Messages Area - Only This Scrolls -->
                    <div class="flex-1 overflow-y-auto p-4 bg-gray-50">
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
                                :class="message.sender.role === 'staff' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm transition-all duration-200 hover:shadow-md"
                                    :class="message.sender.role === 'staff'
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-br-md'
                                        : 'bg-white border border-gray-200 text-gray-900 rounded-bl-md'"
                                >
                                    <p class="text-sm leading-relaxed">{{ message.content }}</p>
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
                    </div>

                    <!-- Message Input Panel - Fixed at Bottom -->
                    <div class="p-4 border-t border-gray-200 bg-white flex-shrink-0 sticky bottom-0 z-10 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <div class="flex items-end gap-3">
                            <div class="flex gap-2">
                                <Button variant="ghost" size="sm" class="p-2">
                                    <Paperclip class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" class="p-2">
                                    <Smile class="h-4 w-4" />
                                </Button>
                            </div>
                            <Input
                                v-model="newMessage"
                                placeholder="Type a message..."
                                class="flex-1 rounded-full border-gray-300 px-4 py-2"
                                @keyup.enter="sendMessage"
                                @input="handleTyping"
                                @keydown.enter="stopTyping"
                                :disabled="sendingMessage"
                            />
                            <Button
                                @click="sendMessage"
                                :disabled="!newMessage.trim() || sendingMessage"
                                class="rounded-full h-10 w-10 p-0 bg-blue-600 hover:bg-blue-700"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
