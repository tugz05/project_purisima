<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import resident from '@/routes/resident';

interface Props {
    items: NavItem[];
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
});

const page = usePage();

const handleClick = (href: string, e: Event) => {
    if (props.disabled && href !== resident.onboarding.show().url) {
        e.preventDefault();
        toast.error('Please complete your profile first to access this feature.');
        router.visit(resident.onboarding.show().url);
    }
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton 
                    as-child 
                    :is-active="urlIsActive(item.href, page.url)" 
                    :tooltip="item.title"
                    :disabled="disabled && item.href !== resident.onboarding.show().url"
                >
                    <Link 
                        :href="item.href"
                        @click="handleClick(item.href, $event)"
                        :class="disabled && item.href !== resident.onboarding.show().url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
