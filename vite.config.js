// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'url';
import i18n from 'laravel-vue-i18n/vite';

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
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/sass/main.scss'],
            refresh: true,
        }),
        i18n(),
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
