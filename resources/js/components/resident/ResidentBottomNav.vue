<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, FileText, User, MessageSquare } from 'lucide-vue-next';
import resident from '@/routes/resident';
import { useAuth } from '@/composables/useAuth';

const { user } = useAuth();
const page = usePage();

const navItems = [
    {
        title: 'Home',
        href: resident.dashboard().url,
        icon: LayoutGrid,
        active: computed(() => page.url === resident.dashboard().url),
    },
            {
                title: 'Support',
                href: '/resident/messaging',
                icon: MessageSquare,
                active: computed(() => page.url.startsWith('/resident/messaging')),

            },
    {
        title: 'Transactions',
        href: resident.transactions.index().url,
        icon: FileText,
        active: computed(() => page.url.startsWith('/resident/transactions')),
    },
    {
        title: 'Account',
        href: '/resident/account',
        icon: User,
        active: computed(() => page.url === '/resident/account'),
    },
];
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg md:hidden">
        <div class="flex items-center justify-around h-16 px-2">
            <Link
                v-for="item in navItems"
                :key="item.title"
                :href="item.href"
                class="flex flex-col items-center justify-center p-2 rounded-lg transition-all duration-200 min-w-0 flex-1"
                :class="[
                    item.active.value
                        ? 'text-blue-600 bg-blue-50'
                        : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                ]"
            >
                <component
                    :is="item.icon"
                    class="h-5 w-5 mb-1"
                    :class="[
                        item.active.value
                            ? 'text-blue-600'
                            : 'text-gray-600'
                    ]"
                />
                <span
                    class="text-xs font-medium truncate"
                    :class="[
                        item.active.value
                            ? 'text-blue-600'
                            : 'text-gray-600'
                    ]"
                >
                    {{ item.title }}
                </span>
            </Link>
        </div>

        <!-- Safe area for devices with home indicator -->
        <div class="h-safe-area-inset-bottom bg-white"></div>
    </nav>
</template>

<style>
/* Safe area support for devices with home indicator */
.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom, 0px);
}

/* Ensure content doesn't get hidden behind the bottom nav */
body {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
