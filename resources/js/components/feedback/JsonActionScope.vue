<script setup lang="ts">
import { runJsonAction } from '@/composables/useJsonActionFeedback';
</script>

<template>
    <slot :run-json-action="runJsonAction" />
</template>
