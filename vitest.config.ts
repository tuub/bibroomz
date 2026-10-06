import vue from "@vitejs/plugin-vue";
import { fileURLToPath } from "url";
import { defineConfig } from "vitest/config";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js/", import.meta.url)),
            "ziggy-js": fileURLToPath(new URL("./vendor/tightenco/ziggy", import.meta.url)),
        },
    },
    test: {
        environment: "happy-dom",
        pool: "vmThreads",
        include: ["packages/**/*.test.ts", "resources/js/**/*.test.ts"],
        coverage: {
            provider: "v8",
            include: ["packages/*/src/**/*.ts", "resources/js/**/*.{ts,vue}"],
            exclude: ["packages/**/*.test.ts", "resources/js/**/*.test.ts", "resources/js/**/*.d.ts"],
            reporter: ["text-summary", "cobertura"],
            reportsDirectory: "build/coverage-js",
            thresholds: {
                branches: 56,
                functions: 52,
                lines: 63,
                statements: 63,
                // The packages here ship to consumers outside this repository
                // and are small enough to cover whole, so they answer for
                // themselves rather than hiding inside the application's
                // numbers. A glob-matched file is held to these thresholds
                // instead of the ones above, not as well as.
                "packages/*/src/**/*.ts": {
                    branches: 97,
                    functions: 100,
                    lines: 99,
                    statements: 99,
                },
            },
        },
    },
});
