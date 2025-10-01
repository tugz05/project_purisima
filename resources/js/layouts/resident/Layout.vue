<script setup lang="ts">
import ResidentResponsiveLayout from '@/components/resident/ResidentResponsiveLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <ResidentResponsiveLayout :breadcrumbs="breadcrumbs">
        <slot />
    </ResidentResponsiveLayout>
</template>






