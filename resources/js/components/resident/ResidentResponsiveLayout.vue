<script setup lang="ts">
import { ref, onMounted, onUnmounted, Teleport } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import ResidentSidebar from '@/components/resident/ResidentSidebar.vue';
import ResidentBottomNav from '@/components/resident/ResidentBottomNav.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from 'vue-sonner';
import { useAuth } from '@/composables/useAuth';
import FloatingChat from './FloatingChat.vue';
import PwaInstallButton from '@/components/PwaInstallButton.vue';
import { logout } from '@/routes';
import resident from '@/routes/resident';
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown, LogOut, MessageSquare, User } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';

const handleLogout = (): void => {
    router.flushAll();
};

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { user, userInitials, isAuthenticated, isProfileCompleted } = useAuth();

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
        <header class="sticky top-0 z-40 divide-y divide-gray-200 border-b border-gray-200 bg-white">
            <PwaInstallButton />
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <img 
                        src="/images/logo/unirespond.jpg" 
                        alt="Uni Respond Logo" 
                        class="h-8 w-8 rounded-lg object-contain"
                        @error="$event.target.style.display='none'"
                    />
                    <h1 class="text-lg font-semibold text-gray-900">Uni Respond</h1>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="flex items-center gap-1 rounded-full p-0.5 pl-0.5 pr-1 text-left transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            aria-label="Account menu"
                            aria-haspopup="menu"
                        >
                            <span class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-gray-200">
                                <img
                                    v-if="user.photo_url"
                                    :src="user.photo_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-600 to-slate-800 text-xs font-semibold text-white"
                                >
                                    {{ userInitials }}
                                </span>
                            </span>
                            <ChevronDown class="h-4 w-4 shrink-0 text-gray-500" aria-hidden="true" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="z-[60] w-52">
                        <DropdownMenuItem as-child>
                            <Link
                                :href="resident.account.edit().url"
                                class="flex w-full cursor-pointer items-center gap-2"
                            >
                                <User class="h-4 w-4" />
                                Edit account
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="isProfileCompleted" as-child>
                            <Link
                                :href="resident.messaging.index().url"
                                class="flex w-full cursor-pointer items-center gap-2"
                            >
                                <MessageSquare class="h-4 w-4" />
                                Messages
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link
                                :href="logout().url"
                                method="post"
                                as="button"
                                class="flex w-full cursor-pointer items-center gap-2 text-red-600 focus:text-red-600"
                                @click="handleLogout"
                            >
                                <LogOut class="h-4 w-4" />
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>

        <!-- Mobile Content -->
        <main class="flex-1 pb-[calc(4.5rem+env(safe-area-inset-bottom,0px))]">
            <slot />
        </main>

        <!-- Bottom Navigation -->
        <ResidentBottomNav />
    </div>

    <!-- Toast Notifications -->
    <Toaster />

    <!-- Floating Chat for authenticated residents only -->
    <Teleport to="body">
        <FloatingChat
            v-if="isAuthenticated && user?.role === 'resident'"
            :initial-conversations="[]"
        />
    </Teleport>
</template>
