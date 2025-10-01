<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import NotificationDropdown from '@/components/staff/NotificationDropdown.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Realtime staff header unread badge
const page = usePage() as any;
const currentUser = page?.props?.auth?.user || null;
const isStaff = currentUser && (currentUser.role === 'staff' || currentUser.role === 'admin');
const headerUnread = ref<number>(page?.props?.unreadCount || 0);
let headerChannel: any = null;

onMounted(() => {
    try {
        if (isStaff && (window as any).Echo && currentUser?.id) {
            headerChannel = (window as any).Echo.private(`App.Models.User.${currentUser.id}`);
            headerChannel.listen('.message.sent', (e: any) => {
                // Ignore own messages
                if (e?.message?.sender?.id === currentUser.id) return;
                headerUnread.value = (headerUnread.value || 0) + 1;
            });
        }
    } catch (_e) {}
});

onUnmounted(() => {
    try {
        if (headerChannel && (window as any).Echo) {
            (window as any).Echo.leave(headerChannel.name || headerChannel.channel || '');
        }
    } catch (_e) {}
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex-1"></div>

        <div class="flex items-center gap-2">
            <!-- Messages quick access (staff) -->
            <Link href="/staff/messaging" class="relative inline-flex items-center justify-center h-9 w-9 rounded-md hover:bg-gray-100 text-gray-600" aria-label="Messages" v-if="isStaff">
                <MessageSquare class="h-5 w-5" />
                <!-- Unread badge (reactive) -->
                <span id="header-unread-badge" v-show="headerUnread > 0" class="absolute -top-1 -right-1 inline-flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-red-500 text-white text-[10px] leading-none">
                    {{ headerUnread }}
                </span>
            </Link>
            <NotificationDropdown />
        </div>
    </header>
</template>
