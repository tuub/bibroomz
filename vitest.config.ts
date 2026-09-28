import vue from "@vitejs/plugin-vue";
import { fileURLToPath } from "url";
import { defineConfig } from "vitest/config";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js/", import.meta.url)),
        },
    },
    test: {
        environment: "happy-dom",
        pool: "vmThreads",
        include: ["resources/js/**/*.test.ts"],
        coverage: {
            provider: "v8",
            include: ["resources/js/**/*.{ts,vue}"],
            exclude: ["resources/js/**/*.test.ts", "resources/js/**/*.d.ts"],
            reporter: ["text-summary", "cobertura"],
            reportsDirectory: "build/coverage-js",
            thresholds: {
                branches: 56,
                functions: 52,
                lines: 63,
                statements: 63,
            },
        },
    },
});
