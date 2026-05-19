<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import StaffLayout from '@/layouts/staff/Layout.vue';
import AdminLayout from '@/layouts/admin/Layout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const role = computed(() => (page.props.auth as any)?.user?.role ?? '');
</script>

<template>
    <StaffLayout v-if="role === 'staff'" :breadcrumbs="breadcrumbs">
        <slot />
    </StaffLayout>
    <AdminLayout v-else-if="role === 'admin'" :breadcrumbs="breadcrumbs">
        <slot />
    </AdminLayout>
    <AppSidebarLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>
