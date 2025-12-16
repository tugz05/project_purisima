<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { LayoutGrid, FileText, User, AlertTriangle, Users } from 'lucide-vue-next';
import resident from '@/routes/resident';
import { useAuth } from '@/composables/useAuth';
import { toast } from 'vue-sonner';

const { user, isProfileCompleted } = useAuth();
const page = usePage();

const handleNavClick = (href: string, e: Event) => {
    // Allow navigation to onboarding and account
    if (href === resident.onboarding.show().url || href === '/resident/account') {
        return;
    }
    
    // Block navigation if profile not completed
    if (!isProfileCompleted.value) {
        e.preventDefault();
        toast.error('Please complete your profile first to access this feature.');
        router.visit(resident.onboarding.show().url);
    }
};

const navItems = [
    {
        title: 'Home',
        href: resident.dashboard().url,
        icon: LayoutGrid,
        active: computed(() => page.url === resident.dashboard().url),
    },
    {
        title: 'Transactions',
        href: resident.transactions.index().url,
        icon: FileText,
        active: computed(() => page.url.startsWith('/resident/transactions')),
    },
    {
        title: 'Calamity Reports',
        href: '/resident/calamity',
        icon: AlertTriangle,
        active: computed(() => page.url.startsWith('/resident/calamity')),
    },
    {
        title: 'Household',
        href: '/resident/household-members',
        icon: Users,
        active: computed(() => page.url.startsWith('/resident/household-members')),
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
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-gray-200/80 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] md:hidden">
        <div class="flex items-center justify-around h-[72px] px-1 pb-safe-area-inset-bottom">
            <Link
                v-for="item in navItems"
                :key="item.title"
                :href="item.href"
                @click="handleNavClick(item.href, $event)"
                class="relative flex flex-col items-center justify-center flex-1 min-w-0 px-2 py-2 rounded-xl transition-all duration-300 ease-out group"
                :class="[
                    item.active.value
                        ? 'text-blue-600'
                        : 'text-gray-500 hover:text-blue-500',
                    !isProfileCompleted && item.href !== resident.onboarding.show().url && item.href !== '/resident/account'
                        ? 'opacity-40 cursor-not-allowed pointer-events-none'
                        : 'cursor-pointer active:scale-95'
                ]"
            >
                <!-- Active indicator background -->
                <div
                    v-if="item.active.value"
                    class="absolute inset-x-2 top-1.5 bottom-1.5 bg-gradient-to-b from-blue-50 to-blue-100/50 rounded-xl border border-blue-200/50 shadow-sm"
                />
                
                <!-- Icon container -->
                <div
                    class="relative z-10 flex items-center justify-center mb-1 transition-transform duration-300"
                    :class="[
                        item.active.value
                            ? 'scale-110'
                            : 'group-hover:scale-105'
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="transition-all duration-300"
                        :class="[
                            item.active.value
                                ? 'h-6 w-6 text-blue-600 drop-shadow-sm'
                                : 'h-5.5 w-5.5 text-gray-500 group-hover:text-blue-500'
                        ]"
                        :stroke-width="item.active.value ? 2.5 : 2"
                    />
                </div>
                
                <!-- Label -->
                <span
                    class="relative z-10 text-[10px] font-semibold leading-tight transition-all duration-300 truncate max-w-full px-1"
                    :class="[
                        item.active.value
                            ? 'text-blue-600 scale-105'
                            : 'text-gray-500 group-hover:text-blue-500'
                    ]"
                >
                    {{ item.title }}
                </span>
                
                <!-- Active dot indicator -->
                <div
                    v-if="item.active.value"
                    class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-blue-600 shadow-sm"
                />
            </Link>
        </div>

        <!-- Safe area for devices with home indicator -->
        <div class="h-safe-area-inset-bottom bg-white/95 backdrop-blur-md"></div>
    </nav>
</template>

<style scoped>
/* Safe area support for devices with home indicator */
.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom, 0px);
}

.pb-safe-area-inset-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

/* Ensure content doesn't get hidden behind the bottom nav */
:global(body) {
    padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px));
}

/* Smooth transitions for all interactive elements */
nav a {
    -webkit-tap-highlight-color: transparent;
}

/* Icon size utility */
.h-5\.5 {
    height: 1.375rem;
}

.w-5\.5 {
    width: 1.375rem;
}
</style>
