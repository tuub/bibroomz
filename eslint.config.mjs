import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import prettier from "eslint-config-prettier";
import vuePlugin from "eslint-plugin-vue";
import globals from "globals";

export default [
    {
        ignores: ["node_modules/**", "public/build/**", "resources/js/ziggy.js"],
    },
    js.configs.recommended,
    ...tsPlugin.configs["flat/recommended"],
    ...vuePlugin.configs["flat/recommended"],
    {
        files: ["**/*.{js,ts,vue}"],
        languageOptions: {
            globals: {
                ...globals.browser,
                axios: "readonly",
                Echo: "readonly",
            },
        },
    },
    {
        files: ["**/*.vue"],
        languageOptions: {
            parserOptions: {
                parser: tsParser,
            },
        },
        rules: {
            "vue/multi-word-component-names": "off",
        },
    },
    prettier,
];
