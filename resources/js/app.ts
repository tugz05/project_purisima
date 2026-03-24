import '../css/app.css';
import '../css/sheet-overrides.css';
import 'sonner/dist/styles.css';
import './pusher';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

document.addEventListener('inertia:success', (event: Event) => {
    const page = (event as CustomEvent<{ page: { props?: Record<string, unknown> } }>).detail?.page;
    const token = page?.props?.csrf_token;
    if (typeof token === 'string' && token.length > 0) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(App, props),
        });
        app.use(plugin).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
