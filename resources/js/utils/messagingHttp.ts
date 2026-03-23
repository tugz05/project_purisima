/**
 * JSON fetch with session cookie + CSRF for Laravel messaging routes.
 */
export function messagingJsonFetch(url: string, options: RequestInit = {}): Promise<Response> {
    const headers = new Headers(options.headers ?? {});
    if (!headers.has('X-CSRF-TOKEN')) {
        headers.set(
            'X-CSRF-TOKEN',
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        );
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
