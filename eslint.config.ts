import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import type { Linter } from "eslint";
import prettier from "eslint-config-prettier";
import vuePlugin from "eslint-plugin-vue";
import globals from "globals";
import { fileURLToPath } from "node:url";

const tsconfigRootDir = fileURLToPath(new URL(".", import.meta.url));
const typeAwareFiles = ["**/*.{ts,vue}"];

const typeAwareRules: Linter.Config["rules"] = {
    "@typescript-eslint/await-thenable": "error",
    "@typescript-eslint/consistent-type-imports": [
        "error",
        {
            prefer: "type-imports",
            fixStyle: "inline-type-imports",
        },
    ],
    "@typescript-eslint/no-explicit-any": "error",
    "@typescript-eslint/no-floating-promises": "error",
    "@typescript-eslint/no-misused-promises": "error",
    "@typescript-eslint/no-unnecessary-type-assertion": "error",
    "@typescript-eslint/require-await": "off",
    "no-restricted-syntax": [
        "error",
        {
            selector: "TSAsExpression[typeAnnotation.type='TSUnknownKeyword']",
            message:
                "Casting to `unknown` bypasses the type checker instead of fixing the underlying type mismatch. If this is a genuine, reviewed exception, disable this rule inline with a comment explaining why.",
        },
    ],
};

export default [
    {
        ignores: ["node_modules/**", "public/build/**", "resources/js/ziggy.js", "**/resources/js/ziggy.js"],
    },
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                axios: "readonly",
                Echo: "readonly",
            },
        },
    },
    js.configs.recommended,
    ...(tsPlugin.configs["flat/recommended"] as Linter.Config[]),
    ...vuePlugin.configs["flat/recommended"],
    {
        files: typeAwareFiles,
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir,
                extraFileExtensions: [".vue"],
            },
        },
        rules: typeAwareRules,
    },
    {
        files: ["**/*.d.ts"],
        rules: {
            "@typescript-eslint/consistent-type-imports": "off",
        },
    },
    {
        files: ["**/*.test.ts"],
        rules: {
            "no-restricted-syntax": "off",
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
