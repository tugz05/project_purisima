import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface User {
    id: number;
    name: string;
    email: string;
    first_name?: string;
    last_name?: string;
    middle_name?: string;
    phone?: string;
    birth_date?: string;
    sex?: string;
    civil_status?: string;
    occupation?: string;
    purok?: string;
    photo_url?: string;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
}

export function useAuth() {
    const page = usePage();

    const user = computed(() => {
        return (page.props.auth?.user ?? {}) as User;
    });

    const isAuthenticated = computed(() => {
        return !!user.value.id;
    });

    const isVerified = computed(() => {
        return !!user.value.email_verified_at;
    });

    const userInitials = computed(() => {
        const fullName = user.value.first_name || user.value.name || '';
        if (!fullName) return '';

        const names = fullName.trim().split(' ');
        if (names.length === 0) return '';
        if (names.length === 1) return names[0].charAt(0).toUpperCase();

        return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
    });

    const displayName = computed(() => {
        return user.value.first_name || user.value.name || 'User';
    });

    const fullName = computed(() => {
        const parts = [
            user.value.first_name,
            user.value.middle_name,
            user.value.last_name
        ].filter(Boolean);

        return parts.length > 0 ? parts.join(' ') : user.value.name || 'User';
    });

    return {
        user,
        isAuthenticated,
        isVerified,
        userInitials,
        displayName,
        fullName,
    };
}
