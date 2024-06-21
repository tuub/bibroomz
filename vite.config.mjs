import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import i18n from "laravel-vue-i18n/vite";
import * as path from "path";
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
        laravel({
            input: ["resources/js/app.js", "resources/sass/main.scss"],
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
    server: {
        watch: {
            ignored: ["**/.devenv/**", "**/.direnv/**", "**/vendor/**"],
        },
    },
});