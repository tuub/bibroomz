// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'url';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/', import.meta.url)),
            ziggy: 'vendor/tightenco/ziggy/dist/vue.es.js',
            // 'vendor/tightenco/ziggy/dist',
            // 'vendor/tightenco/ziggy/dist/vue.es.js' if using the Vue plugin
        }
    }
});
