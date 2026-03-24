/**
 * Read Laravel's XSRF-TOKEN cookie (stays in sync with the session on each response).
 * The CSRF meta tag in app.blade.php is not updated on Inertia visits, so it can be stale.
 */
function readXsrfTokenFromCookie(): string {
    const parts = `; ${document.cookie}`.split('; XSRF-TOKEN=');
    if (parts.length < 2) {
        return '';
    }
    const raw = parts.pop()?.split(';')[0] ?? '';
    if (!raw) {
        return '';
    }
    try {
        return decodeURIComponent(raw);
    } catch {
        return raw;
    }
}

/**
 * Session-authenticated JSON fetch for Laravel web routes (CSRF, cookies, Accept: application/json).
 * Use this instead of Inertia router.post/delete when the server returns plain JSON.
 */
export function laravelJsonFetch(url: string, options: RequestInit = {}): Promise<Response> {
    const headers = new Headers(options.headers ?? {});

    if (typeof FormData !== 'undefined' && options.body instanceof FormData) {
        headers.delete('Content-Type');
    }

    const xsrf = readXsrfTokenFromCookie();
    if (xsrf && !headers.has('X-XSRF-TOKEN')) {
        headers.set('X-XSRF-TOKEN', xsrf);
    }

    if (!headers.has('X-CSRF-TOKEN')) {
        if (!xsrf) {
            headers.set(
                'X-CSRF-TOKEN',
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            );
        }
    }

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }
    headers.set('X-Requested-With', 'XMLHttpRequest');

    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });
}
