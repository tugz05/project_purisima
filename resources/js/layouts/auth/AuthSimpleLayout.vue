<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-blue-50 via-indigo-50 to-cyan-50 p-6 md:p-10">
        <!-- Background decorative elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-20 w-72 h-72 bg-blue-200/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-cyan-200/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md">
            <div class="flex flex-col gap-8">
                <!-- Logo and Branding -->
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center gap-3">
                        <div class="flex items-center justify-center h-20 w-20 rounded-2xl bg-white shadow-lg p-2">
                            <img 
                                src="/images/logo/unirespond.jpg" 
                                alt="Uni Respond Logo" 
                                class="h-full w-full object-contain rounded-lg"
                                @error="$event.target.style.display='none'"
                            />
                        </div>
                        <div class="text-center">
                            <h2 class="text-3xl font-bold tracking-wide text-blue-900 mb-1">Uni Respond</h2>
                            <p class="text-xs text-blue-700 opacity-80">Barangay Safety & Incident Management</p>
                            <div class="flex items-center justify-center gap-1 mt-1 text-xs text-blue-600">
                                <MapPin class="h-3 w-3" />
                                <span>Barangay Purisima, Tago, Surigao del Sur</span>
                            </div>
                        </div>
                    </Link>
                    
                    <!-- Title and Description -->
                    <div class="space-y-2 text-center mt-4">
                        <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                        <p class="text-sm text-gray-600">{{ description }}</p>
                    </div>
                </div>
                
                <!-- Form Content -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-6 md:p-8">
                    <slot />
                </div>

                <!-- Footer -->
                <div class="text-center text-xs text-gray-500">
                    <p>© {{ new Date().getFullYear() }} Uni Respond. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Additional styling if needed */
</style>
