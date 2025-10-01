<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import FloatingChat from '@/components/resident/FloatingChat.vue';

interface Conversation {
    id: number;
    staff: {
        id: number;
        name: string;
        email: string;
    };
    messages: Array<{
        id: number;
        content: string;
        created_at: string;
        is_read: boolean;
        sender: {
            id: number;
            name: string;
        };
    }>;
    unread_count: number;
}

interface StaffMember {
    id: number;
    name: string;
    email: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Props {
    conversations: Conversation[];
    availableStaff: StaffMember[];
    currentUser: User;
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Support Chat" />

    <ResidentLayout>
        <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4">
            <!-- Welcome Message -->
            <div class="text-center max-w-md mx-auto">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mb-6 shadow-lg mx-auto">
                    <FloatingChat class="h-10 w-10 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Need Help?</h1>
                <p class="text-gray-600 mb-6">
                    Chat with our barangay staff anytime for quick assistance with your concerns.
                </p>

                <!-- Quick Actions -->
                <div class="space-y-3">
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Real-time messaging</span>
                    </div>
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Available 24/7</span>
                    </div>
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Quick response</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Chat Component -->
        <FloatingChat
            :initial-conversations="conversations"
        />
    </ResidentLayout>
</template>
