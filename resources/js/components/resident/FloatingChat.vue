<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    MessageCircle,
    Send,
    Minimize2,
    X,
    User,
    Clock,
    CheckCircle,
    CheckCircle2
} from 'lucide-vue-next';

interface Message {
    id: number;
    content: string;
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
const isSending = ref(false);
const isLoading = ref(false);
const typingTimeout = ref<number | null>(null);
const isTyping = ref(false);
const otherUserTyping = ref(false);
const currentChannel = ref<any>(null);
// Utilities to safely add messages without duplicates
const addMessageIfNew = (incomingMessage: any) => {
    if (!currentConversation.value) return;
    const alreadyExists = currentConversation.value.messages.some(m => m.id === incomingMessage.id);
    if (alreadyExists) {
        return;
    }
    currentConversation.value.messages.push(incomingMessage);
    // Auto-scroll to bottom
    setTimeout(() => {
        const messagesContainer = document.querySelector('.overflow-y-auto');
        if (messagesContainer) {
            (messagesContainer as HTMLElement).scrollTop = (messagesContainer as HTMLElement).scrollHeight;
        }
    }, 100);
};

// Computed
const currentUser = computed(() => {
    // Get user from page props or use auth composable
    const page = usePage();
    return page.props.auth?.user || { id: 1, name: 'Current User', role: 'resident' };
});

const unreadCount = computed(() =>
    conversations.value.reduce((count, conv) => count + conv.unread_count, 0)
);

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
        console.log('No existing conversations, creating new one...');
        await createGeneralConversation();
    } else {
        // Use existing conversation
        currentConversation.value = conversations.value[0];
        console.log('Using existing conversation:', currentConversation.value.id);
    }

    if (conversations.value.length > 0) {
        currentConversation.value = conversations.value[0];
    }
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
        // Get CSRF token from Laravel
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || (document.querySelector('input[name="_token"]') as HTMLInputElement)?.value
                       || (window as any).Laravel?.csrfToken;

        console.log('CSRF token found:', csrfToken ? 'Yes' : 'No');
        console.log('CSRF token value:', csrfToken?.substring(0, 10) + '...' || 'None');

        if (!csrfToken) {
            // Try to make a request anyway and let the server tell us what's wrong
            console.log('CSRF token not found, attempting request anyway...');
        }

        const response = await fetch('/resident/messaging/conversations/create-general', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
            },
            body: JSON.stringify({
                content: messageContent.value || 'Hello, I need assistance.'
            })
        });

        console.log('Create conversation response status:', response.status);

        if (response.ok) {
            const data = await response.json();
            console.log('Conversation created:', data);
            if (data.conversation) {
                currentConversation.value = data.conversation;
                conversations.value.unshift(data.conversation);
                messageContent.value = '';

                // Clear loading state immediately after successful creation
                isLoading.value = false;
                console.log('Conversation loaded, loading state cleared');

                // Setup real-time messaging for the new conversation
                setupRealTimeMessaging();
            } else {
                console.error('No conversation data in response:', data);
                isLoading.value = false;
            }
        } else {
            let errorMessage = `Server error (${response.status})`;
            let errorData = null;

            try {
                const responseText = await response.text();
                console.log('Response text:', responseText);

                // Try to parse as JSON
                errorData = JSON.parse(responseText);
                errorMessage = errorData.error || errorData.message || errorMessage;
            } catch (e) {
                console.log('Response is not JSON, likely HTML error page');
                console.log('Raw response:', response.text || 'No response text');
            }

            console.error('Failed to create conversation:', {
                status: response.status,
                statusText: response.statusText,
                errorData: errorData,
                errorMessage: errorMessage
            });
            alert(`Failed to create conversation (${response.status}): ${errorMessage}`);
        }
    } catch (error) {
        console.error('Failed to create general conversation:', error);
        alert('Failed to create conversation. Please check your connection.');
        isLoading.value = false;
    }
};

const sendMessage = async () => {
    if (!messageContent.value.trim() || isSending.value) return;

    isSending.value = true;

    // Stop typing indicator
    stopTyping();

    try {
        // If no conversation exists, create one first
        if (!currentConversation.value) {
            console.log('No current conversation, creating one...');
            await createGeneralConversation();
            if (!currentConversation.value) {
                console.log('Failed to create conversation, stopping send');
                isSending.value = false;
                return;
            }
            console.log('Conversation created successfully:', (currentConversation.value as Conversation).id);
        }

        // Double-check that conversation exists and has valid ID
        if (!currentConversation.value || !currentConversation.value.id) {
            console.error('Invalid conversation state:', currentConversation.value);
            isSending.value = false;
            return;
        }

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || (document.querySelector('input[name="_token"]') as HTMLInputElement)?.value
                       || (window as any).Laravel?.csrfToken;

        console.log('Send message - CSRF token:', csrfToken ? 'Found' : 'Not found');
        console.log('FloatingChat: Sending message to conversation ID:', currentConversation.value.id);

        // Send message using fetch for better error handling
        const response = await fetch(`/resident/messaging/conversations/${currentConversation.value.id}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
            },
            body: JSON.stringify({
                content: messageContent.value,
            })
        });

        if (response.ok) {
            const data = await response.json();
            console.log('FloatingChat: Send message response:', data);
            messageContent.value = '';

            // Update conversation with new message
            if (currentConversation.value && data.message) {
                currentConversation.value.messages.push(data.message);
                console.log('FloatingChat: Added message locally');
            }
        } else {
            let errorMessage = `Server error (${response.status})`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.error || errorData.message || errorMessage;
                console.error('FloatingChat: Send message error:', errorData);
            } catch (e) {
                console.log('Response is not JSON, likely HTML error page');
            }

            // If conversation doesn't exist, try to recreate it
            if (response.status === 404 && errorMessage.includes('No query results')) {
                console.log('Conversation not found, recreating...');
                currentConversation.value = null;
                await createGeneralConversation();
                if (currentConversation.value) {
                    console.log('Conversation recreated, retrying send...');
                    // Retry sending the message
                    return sendMessage();
                }
            }

            console.error('Failed to send message:', errorMessage);
            alert('Failed to send message: ' + errorMessage);
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        alert('Failed to send message. Please check your connection.');
    } finally {
        isSending.value = false;
    }
};

const switchConversation = (conversation: Conversation) => {
    currentConversation.value = conversation;
    // Mark messages as read
    conversation.unread_count = 0;
};

// Typing indicator functions
const handleTyping = () => {
    if (!currentConversation.value || isTyping.value) return;

    isTyping.value = true;

    // Send typing start event
    fetch(`/resident/messaging/conversations/${currentConversation.value.id}/typing/start`, {
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
    if (!currentConversation.value || !isTyping.value) return;

    isTyping.value = false;

    // Send typing stop event
    fetch(`/resident/messaging/conversations/${currentConversation.value.id}/typing/stop`, {
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

// Real-time messaging setup with polling fallback
const setupRealTimeMessaging = () => {
    console.log('FloatingChat: Setting up real-time messaging...');
    console.log('FloatingChat: Current conversation:', currentConversation.value);
    console.log('FloatingChat: Echo object:', window.Echo);

    // Clean up previous channel if exists
    if (currentChannel.value) {
        console.log('FloatingChat: Leaving previous channel:', currentChannel.value);
        if (window.Echo && window.Echo.leave) {
            window.Echo.leave(`conversation.${currentChannel.value}`);
        }
        currentChannel.value = null;
    }

    if (currentConversation.value) {
        const channelName = `conversation.${currentConversation.value.id}`;
        console.log('FloatingChat: Creating private channel:', channelName);

        // Try WebSocket first
        if (window.Echo && window.Echo.private) {
            try {
                const channel = window.Echo.private(channelName);
                currentChannel.value = currentConversation.value.id;

                console.log('FloatingChat: Channel created:', channel);

                // If polling was running earlier, stop it now to avoid duplicates
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }

                // Listen for new messages
                channel.listen('.message.sent', (e: any) => {
                    console.log('FloatingChat: Received real-time message:', e);

                    // Only add message if it's not from current user (avoid duplicates)
                    if (e.message.sender.id !== currentUser.value?.id) {
                        addMessageIfNew(e.message);
                        console.log('FloatingChat: Added message to conversation (Echo)');

                        // If chat is closed or different conversation is selected, bump unread count
                        const targetConversationId = e.conversation?.id || currentConversation.value?.id;
                        if (!isOpen.value || (currentConversation.value && currentConversation.value.id !== targetConversationId)) {
                            const conv = conversations.value.find(c => c.id === targetConversationId);
                            if (conv) {
                                conv.unread_count = (conv.unread_count || 0) + 1;
                            }
                        }
                    }
                });

                // Listen for typing indicators
                channel.listen('.user.typing', (e: any) => {
                    console.log('FloatingChat: Received typing event:', e);
                    // Handle typing indicators
                    if (e.user.id !== currentUser.value?.id) {
                        otherUserTyping.value = e.is_typing;

                        // Auto-hide typing indicator after 3 seconds
                        if (e.is_typing) {
                            setTimeout(() => {
                                otherUserTyping.value = false;
                            }, 3000);
                        }
                    }
                });

                console.log('FloatingChat: Real-time messaging setup complete');
                return;
            } catch (error) {
                console.error('FloatingChat: WebSocket setup failed:', error);
            }
        }

        // Fallback to polling if WebSocket fails
        console.log('FloatingChat: Using polling fallback for real-time updates');
        setupPollingUpdates();
    } else {
        console.log('FloatingChat: No conversation to set up real-time messaging');
    }
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
                const response = await fetch(`/resident/messaging/conversations/${currentConversation.value.id}/json`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.conversation && data.conversation.messages) {
                        const fetchedMessages = data.conversation.messages as any[];
                        const existingIds = new Set(currentConversation.value.messages.map(m => m.id));
                        const deduped = fetchedMessages.filter(m => !existingIds.has(m.id));
                        if (deduped.length > 0) {
                            currentConversation.value.messages.push(...deduped);
                            console.log('FloatingChat: Added', deduped.length, 'new messages via polling');
                            setTimeout(() => {
                                const messagesContainer = document.querySelector('.overflow-y-auto');
                                if (messagesContainer) {
                                    (messagesContainer as HTMLElement).scrollTop = (messagesContainer as HTMLElement).scrollHeight;
                                }
                            }, 100);
                        }
                    }
                }
            } catch (error) {
                console.error('FloatingChat: Polling error:', error);
            }
        }
    }, 2000); // Poll every 2 seconds
};

onMounted(() => {
    console.log('FloatingChat: Component mounted');
    console.log('FloatingChat: Echo available:', !!window.Echo);
    console.log('FloatingChat: Current user:', currentUser.value);

    // Test Echo connection with better error handling
    if (window.Echo) {
        console.log('FloatingChat: Testing Echo connection...');
        try {
            // Wait a bit for Echo to be fully initialized
            setTimeout(() => {
                if (window.Echo && window.Echo.connector) {
                    console.log('FloatingChat: Echo connector available');
                } else {
                    console.error('FloatingChat: Echo connector not available');
                }
            }, 1000);
        } catch (error) {
            console.error('FloatingChat: Error creating test channel:', error);
        }
    } else {
        console.error('FloatingChat: Echo not available!');
    }

    // Initialize conversations if any
    if (props.initialConversations && props.initialConversations.length > 0) {
        conversations.value = props.initialConversations;
        // Auto-select first conversation if available
        if (props.initialConversations[0]) {
            currentConversation.value = props.initialConversations[0];
            setupRealTimeMessaging();
        }
    }
});

// Watch for conversation changes to set up real-time listeners
watch(currentConversation, () => {
    if (currentConversation.value) {
        setupRealTimeMessaging();
    }
});

onBeforeUnmount(() => {
    // Clean up listeners
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }

    // Clean up Echo channel
    if (currentChannel.value && window.Echo && window.Echo.leave) {
        window.Echo.leave(`conversation.${currentChannel.value}`);
    }

    // Clean up polling interval
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
});
</script>

<template>
    <!-- Floating Action Button -->
    <div
        v-if="!isOpen"
        class="fixed right-6 bottom-20 md:bottom-6 md:top-auto"
        style="z-index: 9998 !important; position: fixed !important;"
        :class="unreadCount > 0 ? 'md:right-24' : ''"
    >
    <Button
        @click="openChat"
        class="h-14 w-14 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 bg-blue-600 hover:bg-blue-700 md:h-16 md:w-16 transform hover:scale-110 active:scale-105 animate-pulse-glow"
    >
            <div class="relative">
                <MessageCircle class="h-7 w-7 md:h-8 md:w-8" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-6 w-6 rounded-full flex items-center justify-center text-xs md:-top-2 md:-right-2 md:h-6 md:w-6"
                >
                    {{ unreadCount }}
                </Badge>
            </div>
        </Button>

        <!-- Unread messages indicator -->
        <div v-if="unreadCount > 0" class="absolute -top-1 -right-1 animate-pulse md:top-0 md:right-0">
            <div class="w-3 h-3 bg-red-500 rounded-full md:w-3 md:h-3"></div>
        </div>
    </div>

    <!-- Chat Window -->
    <!-- Professional Enterprise Chat Window with Genie Effects -->
    <Transition
        name="chatbot-genie"
        enter-active-class="duration-500 ease-out"
        leave-active-class="duration-300 ease-in"
        enter-from-class="opacity-0 scale-50 translate-x-full translate-y-full"
        enter-to-class="opacity-100 scale-100 translate-x-0 translate-y-0"
        leave-from-class="opacity-100 scale-100 translate-x-0 translate-y-0"
        leave-to-class="opacity-0 scale-50 translate-x-full translate-y-full"
    >
        <div
            v-if="isOpen"
            class="bg-white rounded-lg border border-gray-300 overflow-hidden transform-gpu"
            :class="isMinimized ? 'w-80 h-14' : 'w-[400px] h-[560px]'"
            style="position: fixed !important; bottom: 24px; right: 24px; z-index: 9999 !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); transform-origin: bottom right;"
        >
        <!-- Professional Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-slate-700 border-b border-slate-600 rounded-t-lg">
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
                    <p class="text-slate-300 text-xs leading-3">Online • Staff Team</p>
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

        <!-- Professional Messages Area -->
        <div v-if="!isMinimized" class="flex flex-col h-full">
            <!-- Messages Container -->
            <div class="px-4 py-3 space-y-2 bg-gray-50 overflow-y-auto" style="height: calc(100% - 140px); flex-shrink: 0;">
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
                        <p class="text-sm leading-relaxed">{{ message.content }}</p>
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
            </div>

        </div>

        <!-- Fixed Input Area - Always at Bottom -->
        <div v-if="!isMinimized" class="absolute bottom-0 left-0 right-0 p-3 bg-white border-t border-gray-200" style="height: 80px; overflow: hidden;">
            <div class="flex gap-2 items-center h-full">
                <div class="flex-1">
                    <Input
                        v-model="messageContent"
                        placeholder="Type a message..."
                        class="rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none w-full"
                        @keydown.enter.prevent="sendMessage"
                        @input="handleTyping"
                        @keydown.enter="stopTyping"
                        :disabled="isSending || isLoading"
                    />
                </div>
                <Button
                    @click="sendMessage"
                    :disabled="!messageContent.trim() || isSending || isLoading"
                    size="sm"
                    class="h-10 px-3 bg-slate-700 hover:bg-slate-800 disabled:bg-gray-300 transition-colors"
                >
                    <Send class="h-4 w-4 text-white" />
                </Button>
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

