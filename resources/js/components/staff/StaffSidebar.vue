<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type AppPageProps, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutGrid, FileText, Users, Settings, FileType, UserCheck, Megaphone, CreditCard, AlertTriangle } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import staff from '@/routes/staff';

const page = usePage<AppPageProps>();

const mainNavItems = computed((): NavItem[] => {
    const pendingTx = Number(page.props.staffPendingTransactionsCount ?? 0);

    return [
        {
            title: 'Dashboard',
            href: staff.dashboard().url,
            icon: LayoutGrid,
        },
        {
            title: 'Transactions',
            href: '/staff/transactions',
            icon: FileText,
            badge: pendingTx > 0 ? pendingTx : undefined,
        },
        {
            title: 'Payments',
            href: staff.payments.history.url(),
            icon: CreditCard,
        },
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
        },
        {
            title: 'Settings',
            href: '#',
            icon: Settings,
        },
    ];
});

const footerNavItems: NavItem[] = [];
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





