import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import i18n from "laravel-vue-i18n/vite";
import * as path from "path";
import Components from "unplugin-vue-components/vite";
import { URL, fileURLToPath } from "url";
import { defineConfig } from "vite";

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
        Components({
            resolvers: [PrimeVueResolver()],
        }),
        laravel({
            input: ["resources/js/app.ts", "resources/sass/main.scss"],
            refresh: true,
        }),
        i18n(),
    ],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js/", import.meta.url)),
            "ziggy-js": path.resolve("vendor/tightenco/ziggy"),
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes("node_modules")) {
                        return undefined;
                    }

                    if (id.includes("/primevue/") || id.includes("/@primevue/")) {
                        return "vendor-primevue";
                    }

                    if (id.includes("/@fullcalendar/")) {
                        return "vendor-fullcalendar";
                    }

                    if (
                        id.includes("/vue/") ||
                        id.includes("/@vue/") ||
                        id.includes("/@inertiajs/") ||
                        id.includes("/pinia")
                    ) {
                        return "vendor-vue";
                    }

                    return undefined;
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ["**/.devenv/**", "**/.direnv/**", "**/vendor/**"],
        },
    },
});
