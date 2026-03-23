<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import NotificationDropdown from '@/components/staff/NotificationDropdown.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';
import { getPusher } from '@/pusher';
import { messagingJsonFetch } from '@/utils/messagingHttp';
import { STAFF_MESSAGING_UNREAD_EVENT } from '@/staffMessagingEvents';
import PwaInstallButton from '@/components/PwaInstallButton.vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Realtime staff header unread badge (syncs with Inertia shared props + messaging page dispatches + Pusher)
const page = usePage() as any;
const currentUser = page?.props?.auth?.user || null;
const isStaff = currentUser && (currentUser.role === 'staff' || currentUser.role === 'admin');

function initialMessagingUnread(): number {
    const m = page?.props?.messagingUnreadCount;
    if (typeof m === 'number') {
        return m;
    }
    return 0;
}

const headerUnread = ref<number>(initialMessagingUnread());
let headerChannelName = '';
let fetchUnreadDebounce: ReturnType<typeof setTimeout> | null = null;

const scheduleUnreadRefresh = (): void => {
    if (!isStaff) {
        return;
    }
    if (fetchUnreadDebounce) {
        clearTimeout(fetchUnreadDebounce);
    }
    fetchUnreadDebounce = setTimeout(async () => {
        fetchUnreadDebounce = null;
        try {
            const r = await messagingJsonFetch('/staff/messaging/unread-count', { method: 'GET' });
            if (r.ok) {
                const d = await r.json();
                const n = Number(d.count);
                if (!Number.isNaN(n)) {
                    headerUnread.value = n;
                }
            }
        } catch {
            // ignore
        }
    }, 280);
};

watch(
    () => page?.props?.messagingUnreadCount,
    (n) => {
        if (typeof n === 'number') {
            headerUnread.value = n;
        }
    },
);

const onStaffMessagingUnread = (ev: Event): void => {
    const d = (ev as CustomEvent<{ count?: number }>).detail?.count;
    if (typeof d === 'number' && !Number.isNaN(d)) {
        headerUnread.value = d;
    }
};

onMounted(() => {
    window.addEventListener(STAFF_MESSAGING_UNREAD_EVENT, onStaffMessagingUnread);
    try {
        const pusher = getPusher();
        if (isStaff && pusher && currentUser?.id) {
            headerChannelName = `private-App.Models.User.${currentUser.id}`;
            const channel = pusher.subscribe(headerChannelName);
            channel.bind('message.sent', (e: any) => {
                if (e?.message?.sender?.id === currentUser.id) {
                    return;
                }
                scheduleUnreadRefresh();
            });
        }
    } catch (_e) {}
});

onUnmounted(() => {
    window.removeEventListener(STAFF_MESSAGING_UNREAD_EVENT, onStaffMessagingUnread);
    if (fetchUnreadDebounce) {
        clearTimeout(fetchUnreadDebounce);
        fetchUnreadDebounce = null;
    }
    try {
        const pusher = getPusher();
        if (headerChannelName && pusher) {
            pusher.unsubscribe(headerChannelName);
        }
    } catch (_e) {}
});
</script>

<template>
    <header
        class="flex min-w-0 shrink-0 flex-col border-b border-sidebar-border/70 transition-[width,height] ease-linear"
    >
        <PwaInstallButton />
        <div
            class="flex h-16 shrink-0 items-center gap-2 px-6 group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
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
                    <span
                        id="header-unread-badge"
                        v-show="headerUnread > 0"
                        class="absolute -top-1 -right-1 inline-flex min-h-4 min-w-[1rem] max-w-[2rem] px-1 items-center justify-center rounded-full bg-red-500 text-white text-[10px] leading-none tabular-nums font-semibold"
                    >
                        {{ headerUnread > 9 ? '9+' : headerUnread }}
                    </span>
                </Link>
                <NotificationDropdown />
            </div>
        </div>
    </header>
</template>
