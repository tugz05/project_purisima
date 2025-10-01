<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import ResidentLayout from '@/layouts/resident/Layout.vue';
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
    MoreVertical
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
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Home', href: '/resident/dashboard' },
    { title: 'Messages', href: '/resident/messaging' },
]);

const searchQuery = ref(props.search || '');
const isLoading = ref(false);

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
    router.get('/resident/messaging',
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
    router.get('/resident/messaging', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const archiveConversation = (conversationId: number) => {
    router.post(`/resident/messaging/conversations/${conversationId}/archive`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

onMounted(() => {
    // Set up real-time updates for unread count
    // This would be implemented with Laravel Echo
});
</script>

<template>
    <Head title="Messages" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-screen bg-gray-50">
            <!-- Left Sidebar: Conversations List -->
            <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                <!-- Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-xl font-semibold text-gray-900">Messages</h1>
                        <div v-if="hasUnreadMessages" class="relative">
                            <Badge variant="destructive" class="text-xs px-2 py-0">
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
                            class="pl-10 pr-4 h-10 rounded-full border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            @keyup.enter="handleSearch"
                        />
                        <Button
                            v-if="searchQuery"
                            variant="ghost"
                            size="sm"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 rounded-full"
                            @click="clearSearch"
                        >
                            ×
                        </Button>
                    </div>
                </div>

                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="!conversations?.data || conversations.data.length === 0" class="flex flex-col items-center justify-center h-full px-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <MessageSquare class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No conversations yet</h3>
                        <p class="text-gray-500 text-center text-sm mb-6">
                            {{ searchQuery ? 'No conversations match your search.' : 'Start chatting with staff to see messages here.' }}
                        </p>
                        <Link href="/resident/messaging/create">
                            <Button class="bg-blue-600 hover:bg-blue-700">
                                <Plus class="h-4 w-4 mr-2" />
                                Start Conversation
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="space-y-1 p-2">
                        <div
                            v-for="conversation in conversations.data"
                            :key="conversation.id"
                            class="group relative rounded-xl cursor-pointer transition-all duration-200 hover:bg-gray-50"
                            :class="{ 'bg-blue-50 border-l-4 border-blue-500': conversation.resident_has_unread }"
                        >
                            <Link
                                :href="`/resident/messaging/conversations/${conversation.id}`"
                                class="block p-4 rounded-xl"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- Avatar with Status -->
                                    <div class="relative">
                                        <Avatar class="h-12 w-12 ring-2 ring-white shadow-sm">
                                            <AvatarImage :src="`https://ui-avatars.com/api/?name=${conversation.staff.name}&background=10b981&color=fff&bold=true`" />
                                            <AvatarFallback class="font-semibold">{{ getInitials(conversation.staff.name) }}</AvatarFallback>
                                        </Avatar>
                                        <!-- Staff indicator with different color -->
                                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-500 border-2 border-white rounded-full"></div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <h3 class="font-semibold text-gray-900 truncate text-sm">
                                                {{ conversation.staff.name }}
                                            </h3>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-500">
                                                    {{ formatLastMessageTime(conversation.last_message_at) }}
                                                </span>
                                                <div v-if="conversation.resident_has_unread" class="flex-shrink-0">
                                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">
                                                {{ conversation.last_message || 'Staff member' }}
                                            </p>
                                            <div v-if="conversation.subject" class="ml-2 flex-shrink-0">
                                                <Badge variant="outline" class="text-xs">
                                                    Staff
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Empty State -->
            <div class="flex-1 bg-gray-50">
                <div class="flex items-center justify-center h-full">
                    <div class="text-center max-w-sm">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <MessageSquare class="h-10 w-10 text-white" />
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Welcome to Messages</h2>
                        <p class="text-gray-600 mb-6">
                            Select a conversation from the sidebar or start a new one with staff.
                        </p>
                        <div class="space-y-2 text-sm text-gray-500">
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                Real-time messaging with staff
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                File sharing
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircle class="h-4 w-4" />
                                Quick response times
                            </div>
                        </div -->
                        <!-- CTA button -->
                        <div class="mt-8">
                            <Link href="/resident/messaging/create">
                                <Button class="bg-green-600 hover:bg-green-700">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Start New Conversation
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
