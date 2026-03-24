<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import SuperadminLayout from '@/layouts/superadmin/Layout.vue';
import superadmin from '@/routes/superadmin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Search, Pencil } from 'lucide-vue-next';

interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

interface RoleOption {
    value: string;
    label: string;
}

interface Props {
    users: {
        data: ManagedUser[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        role?: string;
    };
    roleCounts: Record<string, number>;
    assignableRoles: RoleOption[];
}

const props = defineProps<Props>();

const page = usePage<{ flash?: { success?: string; error?: string } }>();

const searchQuery = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role && props.filters.role !== '' ? props.filters.role : 'all');

const breadcrumbs = [
    { title: 'Dashboard', href: superadmin.dashboard.url() },
    { title: 'Users', href: superadmin.users.index.url() },
];

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const buildUsersIndexUrl = (extra: Record<string, string | number | undefined> = {}): string => {
    const query: Record<string, string | number> = {};
    for (const [key, value] of Object.entries(extra)) {
        if (value !== undefined && value !== '') {
            query[key] = value;
        }
    }
    const s = searchQuery.value.trim();
    if (s) {
        query.search = s;
    }
    if (roleFilter.value !== 'all') {
        query.role = roleFilter.value;
    }

    return Object.keys(query).length > 0
        ? superadmin.users.index.url({ query })
        : superadmin.users.index.url();
};

const applyQuery = (): void => {
    router.visit(buildUsersIndexUrl(), { preserveState: true, replace: true });
};

watch(searchQuery, () => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }
    searchDebounce = setTimeout(() => applyQuery(), 350);
});

watch(roleFilter, () => applyQuery());

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (typeof msg === 'string' && msg.length > 0) {
            toast.success(msg);
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (msg) => {
        if (typeof msg === 'string' && msg.length > 0) {
            toast.error(msg);
        }
    },
    { immediate: true },
);

const roleBadgeVariant = (role: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    switch (role) {
        case 'superadmin':
            return 'default';
        case 'admin':
            return 'secondary';
        case 'staff':
            return 'outline';
        case 'enforcer':
            return 'outline';
        default:
            return 'secondary';
    }
};

const totalLabel = computed(() => {
    const { from, to, total } = props.users;
    if (from == null || to == null) {
        return `0 of ${total}`;
    }
    return `${from}–${to} of ${total}`;
});
</script>

<template>
    <Head title="Users — Superadmin" />

    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full w-full bg-slate-50/80">
            <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">Users &amp; staff</h1>
                        <p class="mt-1 text-slate-600">
                            Update roles for residents, staff, admins, and enforcers.
                        </p>
                    </div>
                </div>

                <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <Card
                        v-for="opt in assignableRoles"
                        :key="opt.value"
                        class="border-slate-200"
                    >
                        <CardHeader class="py-3 pb-1">
                            <CardTitle class="text-sm font-medium text-slate-600">
                                {{ opt.label }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0 pb-3">
                            <p class="text-2xl font-semibold tabular-nums text-slate-900">
                                {{ roleCounts[opt.value] ?? 0 }}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle>Directory</CardTitle>
                        <CardDescription>{{ totalLabel }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    v-model="searchQuery"
                                    type="search"
                                    placeholder="Search name or email…"
                                    class="pl-9"
                                />
                            </div>
                            <Select v-model="roleFilter">
                                <SelectTrigger class="w-full sm:w-48">
                                    <SelectValue placeholder="Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All roles</SelectItem>
                                    <SelectItem
                                        v-for="opt in assignableRoles"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="button" variant="outline" @click="applyQuery">
                                Apply
                            </Button>
                        </div>

                        <div class="rounded-md border border-slate-200">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead class="w-[100px] text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="u in users.data" :key="u.id">
                                        <TableCell class="font-medium">{{ u.name }}</TableCell>
                                        <TableCell class="text-slate-600">{{ u.email }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="roleBadgeVariant(u.role)">
                                                {{ u.role }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="superadmin.users.edit.url(u.id)">
                                                    <Pencil class="mr-1 h-4 w-4" />
                                                    Edit
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="users.data.length === 0">
                                        <TableCell colspan="4" class="py-10 text-center text-slate-500">
                                            No users match your filters.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div
                            v-if="users.last_page > 1"
                            class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600"
                        >
                            <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="users.current_page <= 1"
                                    @click="router.visit(buildUsersIndexUrl({ page: users.current_page - 1 }))"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="users.current_page >= users.last_page"
                                    @click="router.visit(buildUsersIndexUrl({ page: users.current_page + 1 }))"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </SuperadminLayout>
</template>
