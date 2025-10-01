<template>
    <div class="bg-gradient-to-br from-blue-50 via-white to-teal-50 min-h-full w-full">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
            <!-- Welcome Section -->
            <div class="mb-6 md:mb-8">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 md:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                        <div class="flex items-center gap-6">
                            <img
                                v-if="user.photo_url"
                                :src="user.photo_url"
                                alt="Profile photo"
                                class="h-20 w-20 rounded-full object-cover ring-4 ring-blue-200 shadow-lg"
                            />
                            <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center ring-4 ring-blue-200 shadow-lg" v-else>
                                <span class="text-2xl font-bold text-gray-500">{{ userInitials }}</span>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                Welcome back, {{ displayName }}!
                            </h1>
                            <div class="text-lg text-gray-600 mb-4">
                                <span class="font-medium">Purok:</span> {{ user.purok || 'Not specified' }}
                            </div>
                            <Link
                                href="/resident/account"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-teal-600 hover:from-blue-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Account
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-6 md:mb-8">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Quick Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <Link
                        href="/resident/transactions"
                        class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 p-4 md:p-6 border border-gray-100 hover:border-blue-200"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">My Transactions</h3>
                                <p class="text-gray-600">View and manage your document requests</p>
                            </div>
                        </div>
                    </Link>

                    <Link
                        href="/resident/transactions/create"
                        class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 p-4 md:p-6 border border-gray-100 hover:border-teal-200"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center group-hover:bg-teal-200 transition-colors duration-200">
                                <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors duration-200">New Request</h3>
                                <p class="text-gray-600">Submit a new document request</p>
                            </div>
                        </div>
                    </Link>

                    <Link
                        href="/resident/account"
                        class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 p-4 md:p-6 border border-gray-100 hover:border-purple-200"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-purple-600 transition-colors duration-200">Profile Settings</h3>
                                <p class="text-gray-600">Update your personal information</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mb-6 md:mb-8">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Recent Activity</h2>
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 md:p-6">
                    <div class="text-center py-12">
                        <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No recent activity</h3>
                        <p class="text-gray-600 mb-4">Your recent transactions will appear here</p>
                        <Link
                            href="/resident/transactions/create"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-teal-600 hover:from-blue-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Your First Request
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';

const { user, displayName, userInitials } = useAuth();

defineOptions({
    name: 'ResidentDashboard',
    layout: ResidentLayout,
});
</script>
