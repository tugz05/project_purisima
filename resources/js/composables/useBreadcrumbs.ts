import { computed } from 'vue';
// Avoid route helper runtime dependency that referenced undefined queryParams
const DASHBOARD_URL = '/dashboard';
import staff from '@/routes/staff';
import resident from '@/routes/resident';
import { type BreadcrumbItem } from '@/types';

export interface BreadcrumbConfig {
    title: string;
    href?: string;
    current?: boolean;
}

export function useBreadcrumbs() {
    const createBreadcrumbs = (items: BreadcrumbConfig[]) => {
        return items.map(item => ({
            title: item.title,
            href: item.href || '#',
        })) as BreadcrumbItem[];
    };

    // Dashboard breadcrumbs (empty for root dashboards)
    const dashboardBreadcrumbs = computed(() => []);

    // Staff breadcrumbs
    const staffDashboardBreadcrumbs = computed(() => []);

    const staffTransactionsBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Transactions', href: staff.transactions.index().url },
        ])
    );

    const staffTransactionShowBreadcrumbs = computed(() => (transactionId: string) =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Transactions', href: staff.transactions.index().url },
            { title: `Transaction ${transactionId}`, href: staff.transactions.show(transactionId).url },
        ])
    );

    const staffDocumentTypesBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Document Types', href: '/staff/document-types' },
        ])
    );

    const staffBarangayOfficialsBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Barangay Officials', href: '/staff/barangay-officials' },
        ])
    );

    const staffAnnouncementsBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Announcements', href: '/staff/announcements' },
        ])
    );

    // Resident breadcrumbs
    const residentDashboardBreadcrumbs = computed(() => []);

    const residentTransactionsBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'My Transactions', href: resident.transactions.index().url },
        ])
    );

    const residentTransactionCreateBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'My Transactions', href: resident.transactions.index().url },
            { title: 'Create Transaction', href: resident.transactions.create().url },
        ])
    );

    const residentTransactionShowBreadcrumbs = computed(() => (transactionId: string) =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'My Transactions', href: resident.transactions.index().url },
            { title: `Transaction ${transactionId}`, href: resident.transactions.show(transactionId).url },
        ])
    );

    const residentTransactionEditBreadcrumbs = computed(() => (transactionId: string) =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'My Transactions', href: resident.transactions.index().url },
            { title: `Edit Transaction ${transactionId}`, href: resident.transactions.edit(transactionId).url },
        ])
    );

    const residentAccountBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Dashboard', href: DASHBOARD_URL },
            { title: 'Account', href: '/resident/account' },
        ])
    );

    const residentOnboardingBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Complete Profile', href: '/resident/onboarding' },
        ])
    );

    // Admin breadcrumbs
    const adminDashboardBreadcrumbs = computed(() => []);

    // Enforcer breadcrumbs
    const enforcerDashboardBreadcrumbs = computed(() => []);

    // Settings breadcrumbs
    const profileSettingsBreadcrumbs = computed(() =>
        createBreadcrumbs([
            { title: 'Profile settings', href: '/settings/profile' },
        ])
    );

    return {
        // Generic
        createBreadcrumbs,

        // Dashboard breadcrumbs (empty for root pages)
        dashboardBreadcrumbs,

        // Staff
        staffDashboardBreadcrumbs,
        staffTransactionsBreadcrumbs,
        staffTransactionShowBreadcrumbs,
        staffDocumentTypesBreadcrumbs,
        staffBarangayOfficialsBreadcrumbs,
        staffAnnouncementsBreadcrumbs,

        // Resident
        residentDashboardBreadcrumbs,
        residentTransactionsBreadcrumbs,
        residentTransactionCreateBreadcrumbs,
        residentTransactionShowBreadcrumbs,
        residentTransactionEditBreadcrumbs,
        residentAccountBreadcrumbs,
        residentOnboardingBreadcrumbs,

        // Admin
        adminDashboardBreadcrumbs,

        // Enforcer
        enforcerDashboardBreadcrumbs,

        // Settings
        profileSettingsBreadcrumbs,
    };
}
