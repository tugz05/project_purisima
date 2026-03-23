import Pusher from 'pusher-js';

let pusherInstance = null;

const hasPusherConfig =
    import.meta.env.VITE_PUSHER_APP_KEY && import.meta.env.VITE_PUSHER_APP_KEY.trim() !== '';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function getPusher() {
    if (!hasPusherConfig) {
        return null;
    }

    if (!pusherInstance) {
        const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1';
        pusherInstance = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
            cluster,
            forceTLS: true,
            channelAuthorization: {
                transport: 'ajax',
                endpoint: '/broadcasting/auth',
                headersProvider: () => ({
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                }),
            },
            enabledTransports: ['ws', 'wss', 'xhr_streaming', 'xhr_polling'],
        });
    }

    return pusherInstance;
}

export function isPusherAvailable() {
    return hasPusherConfig;
}
