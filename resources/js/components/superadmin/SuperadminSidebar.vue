<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, UserCog, Users } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import superadmin from '@/routes/superadmin';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: superadmin.dashboard.url(),
        icon: LayoutGrid,
    },
    {
        title: 'All users',
        href: superadmin.users.index.url(),
        icon: Users,
    },
    {
        title: 'Staff accounts',
        href: superadmin.users.index.url({ query: { role: 'staff' } }),
        icon: UserCog,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="superadmin.dashboard.url()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain group-label="Superadmin" :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
