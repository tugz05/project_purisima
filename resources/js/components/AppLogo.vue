<script setup lang="ts">
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
        <img 
            src="/images/logo/unirespond.jpg" 
            alt="Uni Respond Logo" 
            class="h-full w-full object-contain"
            @error="$event.target.style.display='none'"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">Uni Respond</span>
    </div>
</template>
