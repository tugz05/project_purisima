<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, FileText, Users, Settings, FileType, UserCheck, Megaphone, Bell, MessageSquare, ClipboardList, CreditCard, AlertTriangle } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import staff from '@/routes/staff';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: staff.dashboard().url,
        icon: LayoutGrid,
    },
    {
        title: 'Transactions',
        href: '/staff/transactions',
        icon: FileText,
        children: [
            {
                title: 'All Transactions',
                href: '/staff/transactions',
            },
            {
                title: 'Pending',
                href: '/staff/transactions?status=pending',
            },
            {
                title: 'In Progress',
                href: '/staff/transactions?status=in_progress',
            },
            {
                title: 'Completed',
                href: '/staff/transactions?status=completed',
            },
        ],
    },
            // {
            //     title: 'Messages',
            //     href: '/staff/messaging',
            //     icon: MessageSquare,
            //     },
    {
        title: 'Document Types',
        href: '/staff/document-types',
        icon: FileType,
    },

    {
        title: 'Barangay Officials',
        href: '/staff/barangay-officials',
        icon: UserCheck,
    },
    {
        title: 'Announcements',
        href: '/staff/announcements',
        icon: Megaphone,
    },
    {
        title: 'Residents',
        href: '/staff/residents',
        icon: Users,
    },
    {
        title: 'Calamity Reports',
        href: '/staff/calamity',
        icon: AlertTriangle,
        children: [
            {
                title: 'All Reports',
                href: '/staff/calamity',
            },
            {
                title: 'Map View',
                href: '/staff/calamity/map',
            },
        ],
    },
    {
        title: 'Settings',
        href: '#',
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="staff.dashboard().url">
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





