<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import SuperadminLayout from '@/layouts/superadmin/Layout.vue';
import superadmin from '@/routes/superadmin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-vue-next';

interface RoleOption {
    value: string;
    label: string;
}

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        email_verified_at: string | null;
        created_at: string;
    };
    assignableRoles: RoleOption[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: superadmin.dashboard.url() },
    { title: 'Users', href: superadmin.users.index.url() },
    { title: 'Edit user', href: superadmin.users.edit.url(props.user.id) },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
});

const submit = (): void => {
    form.put(superadmin.users.update.url(props.user.id));
};
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full w-full bg-slate-50/80">
            <div class="mx-auto w-full max-w-xl px-4 py-6 sm:px-6 lg:px-8">
                <Button variant="ghost" class="mb-4 -ml-2 gap-2" as-child>
                    <Link :href="superadmin.users.index.url()">
                        <ArrowLeft class="h-4 w-4" />
                        Back to users
                    </Link>
                </Button>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle>Edit user</CardTitle>
                        <CardDescription>
                            Change account details and role. At least one superadmin must always remain.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="space-y-2">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    autocomplete="name"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    required
                                />
                                <p v-if="form.errors.email" class="text-sm text-destructive">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="role">Role</Label>
                                <Select v-model="form.role">
                                    <SelectTrigger id="role">
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="opt in assignableRoles"
                                            :key="opt.value"
                                            :value="opt.value"
                                        >
                                            {{ opt.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role" class="text-sm text-destructive">
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div class="rounded-md border border-slate-200 bg-slate-50/80 px-3 py-2 text-xs text-slate-600">
                                <p>User ID: {{ user.id }}</p>
                                <p v-if="user.email_verified_at">Email verified: {{ user.email_verified_at }}</p>
                                <p v-else>Email not verified</p>
                                <p>Created: {{ user.created_at }}</p>
                            </div>

                            <div class="flex gap-3">
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                                </Button>
                                <Button type="button" variant="outline" as-child>
                                    <Link :href="superadmin.users.index.url()">Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </SuperadminLayout>
</template>
