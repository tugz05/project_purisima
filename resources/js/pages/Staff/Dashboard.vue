<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText, Users, FileCheck, TrendingUp } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Link } from '@inertiajs/vue3';

// Composables
const { staffDashboardBreadcrumbs } = useBreadcrumbs();

// Breadcrumbs
const breadcrumbs = staffDashboardBreadcrumbs.value;
</script>

<template>
    <Head title="Staff Dashboard" />

    <StaffLayout :breadcrumbs="breadcrumbs">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6 max-w-none">
                <!-- Header Section -->
                <div class="mb-6 md:mb-8">
                    <div class="space-y-2">
                        <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 bg-gradient-to-r from-gray-900 to-blue-900 bg-clip-text text-transparent">
                            Staff Dashboard
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 font-medium">Manage transactions and document types</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Link href="/staff/transactions">
                        <Card class="shadow-lg border-gray-200 hover:shadow-xl transition-all duration-300 cursor-pointer group hover:border-blue-300">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-3 text-lg">
                                    <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                                        <FileText class="h-6 w-6 text-blue-600" />
                                    </div>
                                    Transactions
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-gray-600">
                                    View and manage all document requests
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/staff/document-types">
                        <Card class="shadow-lg border-gray-200 hover:shadow-xl transition-all duration-300 cursor-pointer group hover:border-green-300">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-3 text-lg">
                                    <div class="p-2 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors">
                                        <FileCheck class="h-6 w-6 text-green-600" />
                                    </div>
                                    Document Types
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-gray-600">
                                    Manage document types and requirements
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>

                    <Card class="shadow-lg border-gray-200 hover:shadow-xl transition-all duration-300 cursor-pointer group hover:border-purple-300">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-3 text-lg">
                                <div class="p-2 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                                    <Users class="h-6 w-6 text-purple-600" />
                                </div>
                                Residents
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-gray-600">
                                Manage resident profiles and accounts
                            </CardDescription>
                        </CardContent>
                    </Card>

                    <Card class="shadow-lg border-gray-200 hover:shadow-xl transition-all duration-300 cursor-pointer group hover:border-orange-300">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-3 text-lg">
                                <div class="p-2 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                                    <TrendingUp class="h-6 w-6 text-orange-600" />
                                </div>
                                Reports
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-gray-600">
                                View system reports and analytics
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Activity -->
                <Card class="shadow-lg border-gray-200">
                    <CardHeader>
                        <CardTitle class="text-xl">Recent Activity</CardTitle>
                        <CardDescription>Latest transactions and updates</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-8 text-gray-500">
                            <FileText class="h-12 w-12 mx-auto mb-4 text-gray-300" />
                            <p>No recent activity to display</p>
                            <p class="text-sm">Activity will appear here as transactions are processed</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StaffLayout>
</template>

