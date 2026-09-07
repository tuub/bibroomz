import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import vue from "@vitejs/plugin-vue";
import { execFileSync } from "child_process";
import laravel from "laravel-vite-plugin";
import i18n from "laravel-vue-i18n/vite";
import * as path from "path";
import Components from "unplugin-vue-components/vite";
import { URL, fileURLToPath } from "url";
import { type Plugin, defineConfig } from "vite";

function ziggyGenerate(): Plugin {
    return {
        name: "ziggy-generate",
        buildStart() {
            try {
                execFileSync("php", ["artisan", "ziggy:generate"], { stdio: "inherit" });
            } catch (error) {
                if ((error as NodeJS.ErrnoException).code === "ENOENT") {
                    return;
                }

                throw error;
            }
        },
    };
}

export default defineConfig({
    plugins: [
        ziggyGenerate(),
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
            input: ["resources/js/app.ts", "resources/css/main.css"],
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
            checks: {
                pluginTimings: false,
            },
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
