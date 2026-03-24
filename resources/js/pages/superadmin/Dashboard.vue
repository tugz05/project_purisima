<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SuperadminLayout from '@/layouts/superadmin/Layout.vue';
import superadmin from '@/routes/superadmin';
import { Users, Shield } from 'lucide-vue-next';

const breadcrumbs = [{ title: 'Dashboard', href: superadmin.dashboard.url() }];
</script>

<template>
    <Head title="Superadmin Dashboard" />

    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full w-full bg-gradient-to-br from-slate-50 to-violet-50/40">
            <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="mb-8 space-y-2">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
                        Superadmin
                    </h1>
                    <p class="text-lg text-slate-600">
                        Manage every user account and staff role across the system.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <Link :href="superadmin.users.index.url()">
                        <Card class="h-full border-slate-200 shadow-md transition-all hover:border-violet-300 hover:shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-3 text-lg">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100">
                                        <Users class="h-5 w-5 text-violet-700" />
                                    </span>
                                    All users
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-slate-600">
                                    Search, filter by role, and edit names, emails, and permissions.
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link :href="superadmin.users.index.url({ query: { role: 'staff' } })">
                        <Card class="h-full border-slate-200 shadow-md transition-all hover:border-violet-300 hover:shadow-lg">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-3 text-lg">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100">
                                        <Shield class="h-5 w-5 text-violet-700" />
                                    </span>
                                    Staff accounts
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-slate-600">
                                    Jump to barangay staff and administrators in one list.
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>
