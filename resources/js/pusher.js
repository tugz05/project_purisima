import Pusher from 'pusher-js';

let pusherInstance = null;

const hasPusherConfig =
    import.meta.env.VITE_PUSHER_APP_KEY &&
    import.meta.env.VITE_PUSHER_APP_KEY.trim() !== '';

export function getPusher() {
    if (!hasPusherConfig) return null;

    if (!pusherInstance) {
        const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1';
        pusherInstance = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
            cluster,
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            },
            enabledTransports: ['ws', 'wss', 'xhr_streaming', 'xhr_polling'],
        });
    }

    return pusherInstance;
}

export function isPusherAvailable() {
    return hasPusherConfig;
}
