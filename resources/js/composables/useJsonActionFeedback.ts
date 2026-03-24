import { toast } from 'vue-sonner';
import { laravelJsonFetch } from '@/utils/laravelJsonFetch';

export type JsonActionOptions<T = unknown> = {
    /** Shown when response is ok. Set to false to skip. */
    successToast?: string | false;
    /** Shown when response is not ok. Set to false to skip (caller handles UI). */
    errorToast?: string | false;
    onSuccess?: (data: T) => void;
    onError?: (message: string, status: number, data: T | null) => void;
};

function extractErrorMessage(data: unknown, status: number): string {
    if (data && typeof data === 'object') {
        const d = data as Record<string, unknown>;
        if (typeof d.message === 'string' && d.message.length > 0) {
            return d.message;
        }
        const errors = d.errors;
        if (errors && typeof errors === 'object') {
            const firstKey = Object.keys(errors as object)[0];
            const first = firstKey ? (errors as Record<string, unknown>)[firstKey] : undefined;
            if (Array.isArray(first) && typeof first[0] === 'string') {
                return first[0];
            }
        }
    }
    if (status === 403) {
        return 'You are not allowed to perform this action.';
    }
    if (status === 404) {
        return 'The requested resource was not found.';
    }
    return `Request failed (${status})`;
}

/**
 * Call a Laravel JSON endpoint (not Inertia). Shows Sonner toasts and avoids
 * "plain JSON response was received" Inertia errors.
 */
export async function runJsonAction<T = unknown>(
    url: string,
    init: RequestInit & { method?: string } = {},
    options: JsonActionOptions<T> = {},
): Promise<{ ok: boolean; status: number; data: T | null }> {
    const response = await laravelJsonFetch(url, init);

    let data: T | null = null;
    try {
        const text = await response.text();
        if (text) {
            data = JSON.parse(text) as T;
        }
    } catch {
        data = null;
    }

    if (response.ok) {
        if (options.successToast !== false && options.successToast) {
            toast.success(options.successToast);
        }
        options.onSuccess?.(data as T);
        return { ok: true, status: response.status, data };
    }

    const message = extractErrorMessage(data, response.status);
    if (options.errorToast !== false) {
        toast.error(options.errorToast ?? message);
    }
    options.onError?.(message, response.status, data);
    return { ok: false, status: response.status, data };
}

/**
 * Vue composable — same helpers for use in script setup.
 */
export function useJsonActionFeedback(): {
    runJsonAction: typeof runJsonAction;
} {
    return { runJsonAction };
}
