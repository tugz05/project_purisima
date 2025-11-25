<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, FileText, Receipt, Building, UserCheck, FileCheck, CreditCard, FileX, Heart, Users, MessageSquare, AlertTriangle } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import resident from '@/routes/resident';
import { useAuth } from '@/composables/useAuth';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

const { isProfileCompleted } = useAuth();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: resident.dashboard().url,
            icon: LayoutGrid,
        },
        {
            title: 'Transactions',
            href: resident.transactions.index().url,
            icon: FileText,
            children: [
                {
                    title: 'All Transactions',
                    href: resident.transactions.index().url,
                },
                {
                    title: 'Barangay Clearance',
                    href: resident.transactions.index().url + '?type=barangay_clearance',
                },
                {
                    title: 'Residency Certificate',
                    href: resident.transactions.index().url + '?type=residency_certificate',
                },
                {
                    title: 'Business Permit',
                    href: resident.transactions.index().url + '?type=business_permit',
                },
                {
                    title: 'Indigency Certificate',
                    href: resident.transactions.index().url + '?type=indigency_certificate',
                },
                {
                    title: 'Cedula',
                    href: resident.transactions.index().url + '?type=cedula',
                },
                {
                    title: 'Other Documents',
                    href: resident.transactions.index().url + '?type=other',
                },
            ],
        },
        {
            title: 'Calamity Reports',
            href: '/resident/calamity',
            icon: AlertTriangle,
            children: [
                {
                    title: 'My Reports',
                    href: '/resident/calamity',
                },
                {
                    title: 'Location Map',
                    href: '/resident/calamity/map',
                },
            ],
        },
    ];
    
    return items;
});

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
                        <Link :href="resident.dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :disabled="!isProfileCompleted" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    </template>





