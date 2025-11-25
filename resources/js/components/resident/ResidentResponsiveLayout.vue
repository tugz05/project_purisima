<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, Teleport } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import ResidentSidebar from '@/components/resident/ResidentSidebar.vue';
import ResidentBottomNav from '@/components/resident/ResidentBottomNav.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from 'vue-sonner';
import { useAuth } from '@/composables/useAuth';
import FloatingChat from './FloatingChat.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { user, userInitials } = useAuth();

// Detect if we're on mobile based on screen size
const isMobile = ref(false);

const updateMobileState = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 768; // md breakpoint
    }
};

onMounted(() => {
    updateMobileState();
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateMobileState);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateMobileState);
    }
});
</script>

<template>
    <!-- Desktop Layout with Sidebar -->
    <AppShell v-if="!isMobile" variant="sidebar" class="hidden md:flex">
        <ResidentSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>

    <!-- Mobile Layout with Bottom Navigation -->
    <div v-else class="flex flex-col min-h-screen bg-gray-50 md:hidden">
        <!-- Mobile Header -->
        <header class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img 
                        src="/images/logo/unirespond.jpg" 
                        alt="Uni Respond Logo" 
                        class="h-8 w-8 rounded-lg object-contain"
                        @error="$event.target.style.display='none'"
                    />
                    <h1 class="text-lg font-semibold text-gray-900">Uni Respond</h1>
                </div>
                <div class="flex items-center gap-2">
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        alt="Profile"
                        class="h-8 w-8 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center"
                    >
                        <span class="text-xs font-medium text-gray-600">
                            {{ userInitials }}
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Content -->
        <main class="flex-1 pb-20">
            <slot />
        </main>

        <!-- Bottom Navigation -->
        <ResidentBottomNav />
    </div>

    <!-- Toast Notifications -->
    <Toaster />

    <!-- Floating Chat for Residents -->
    <Teleport to="body">
        <FloatingChat
            v-if="user?.role === 'resident'"
            :initial-conversations="[]"
        />
    </Teleport>
</template>
