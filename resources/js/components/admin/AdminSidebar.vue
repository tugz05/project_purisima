<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Users, MapPin, UserCircle, Settings } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import admin from '@/routes/admin';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard().url,
        icon: LayoutGrid,
    },
    {
        title: 'Staff Management',
        href: admin.staff.index().url,
        icon: Users,
    },
    {
        title: 'Purok Management',
        href: admin.puroks.index().url,
        icon: MapPin,
    },
    {
        title: 'Residents',
        href: admin.residents.index().url,
        icon: UserCircle,
    },
    {
        title: 'System Settings',
        href: admin.settings.index().url,
        icon: Settings,
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
                        <Link :href="admin.dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
