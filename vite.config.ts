import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const skipWayfinder = env.SKIP_WAYFINDER === '1' || env.SKIP_WAYFINDER === 'true';
    const phpBin = env.WAYFINDER_PHP_BIN?.trim() || 'php';

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
            tailwindcss(),
            ...(skipWayfinder
                ? []
                : [
                      wayfinder({
                          formVariants: true,
                          command: `${phpBin} artisan wayfinder:generate`,
                      }),
                  ]),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    };
});
