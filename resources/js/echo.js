import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Prevent PusHER from auto-connecting
window.Pusher = Pusher;

// Enable Reverb for real-time broadcasting
const ENABLE_REVERB = true;

// Check if Reverb credentials are configured
const hasReverbConfig = ENABLE_REVERB &&
                       import.meta.env.VITE_REVERB_APP_KEY &&
                       import.meta.env.VITE_REVERB_APP_KEY.trim() !== '';

if (hasReverbConfig) {
    console.log('Initializing Reverb with credentials');
    console.log('Reverb Key:', import.meta.env.VITE_REVERB_APP_KEY);
    console.log('Reverb Host:', import.meta.env.VITE_REVERB_HOST || '127.0.0.1');
    console.log('Reverb Port:', import.meta.env.VITE_REVERB_PORT || 8080);
    console.log('Current URL:', window.location.href);
    console.log('User Agent:', navigator.userAgent);

    const REVERB_HOST = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const REVERB_SCHEME = (import.meta.env.VITE_REVERB_SCHEME || 'https').toLowerCase();
    const FORCE_TLS = REVERB_SCHEME === 'https';
    const WS_PORT = Number(import.meta.env.VITE_REVERB_PORT || (FORCE_TLS ? 443 : 80));
    const WSS_PORT = Number(import.meta.env.VITE_REVERB_WSS_PORT || WS_PORT);

    console.log('[Echo] Using host:', REVERB_HOST, 'scheme:', REVERB_SCHEME, 'wsPort:', WS_PORT, 'wssPort:', WSS_PORT);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: REVERB_HOST,
        wsPort: WS_PORT,
        wssPort: WSS_PORT,
        forceTLS: FORCE_TLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        },
    });

    // Add connection event listeners with better error handling
    const setupEchoListeners = () => {
        try {
            const pusher = window.Echo?.connector?.pusher;
            const connection = pusher?.connection;

            if (!connection) {
                console.log('⚠️ Echo connector available but Pusher connection not ready');
                return false;
            }

            console.log('✅ Echo connector and Pusher connection available');

            connection.bind('connected', () => {
                console.log('✅ Reverb connected successfully (Pusher protocol)!');
                console.log('Echo instance:', window.Echo);
            });

            connection.bind('disconnected', () => {
                console.log('❌ Reverb disconnected');
            });

            connection.bind('error', (error) => {
                console.error('❌ Reverb connection error:', error);
            });

            // Do not subscribe to arbitrary test channels to avoid 403s

            return true;
        } catch (e) {
            console.error('❌ Error setting up Echo/Pusher listeners:', e);
            return false;
        }
    };

    // Try to setup listeners immediately
    if (!setupEchoListeners()) {
        // If not ready, try again with delays
        setTimeout(() => {
            if (!setupEchoListeners()) {
                setTimeout(() => {
                    setupEchoListeners();
                }, 1000);
            }
        }, 500);
    }
} else {
    console.log('Reverb credentials not found, using mock Echo for development');
    // Mock Echo object for development when Reverb is not configured
    window.Echo = {
        private: (channel) => ({
            listen: (event, callback) => {
                console.log(`Mock: Listening to ${channel} for event: ${event}`);
                return { stopListening: () => {} };
            },
            stopListening: () => {},
        }),
        channel: (channel) => ({
            listen: (event, callback) => {
                console.log(`Mock: Listening to ${channel} for event: ${event}`);
                return { stopListening: () => {} };
            },
            stopListening: () => {},
        }),
        leave: (channel) => {
            console.log(`Mock: Leaving channel ${channel}`);
        },
        disconnect: () => {
            console.log('Mock: Echo disconnected');
        },
    };
}

export default window.Echo;
