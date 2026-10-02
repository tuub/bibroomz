import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import type { Linter } from "eslint";
import prettier from "eslint-config-prettier";
import importX from "eslint-plugin-import-x";
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
        ignores: [
            "node_modules/**",
            "packages/*/dist/**",
            "public/build/**",
            "resources/js/ziggy.js",
            "**/resources/js/ziggy.js",
        ],
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
    // Anything imported from here is compiled into public/build and downloaded
    // by every visitor, whichever section of package.json it is declared in.
    // So the declaration has to match: a package this code imports belongs in
    // "dependencies", and what is left in "devDependencies" is what only the
    // build and the tooling run. Nothing checked that before and eight packages
    // sat on the wrong side of it, five of them imported from here.
    //
    // Tests are exempt -- they are not built -- and so are the config files at
    // the repository root, which the rule never looks at.
    {
        files: ["resources/js/**/*.{ts,vue}"],
        ignores: ["resources/js/**/*.test.ts"],
        plugins: { "import-x": importX },
        settings: {
            // Neither of these is a package: "@/" is this project's own source
            // tree, and "ziggy-js" is a tsconfig path and a Vite alias onto the
            // composer package that generates the route helpers.
            "import-x/internal-regex": "^@/",
            "import-x/core-modules": ["ziggy-js"],
        },
        rules: {
            // Type-only imports are left out by default, which is right: they
            // are erased before anything is bundled.
            "import-x/no-extraneous-dependencies": [
                "error",
                {
                    devDependencies: false,
                    optionalDependencies: false,
                    peerDependencies: false,
                },
            ],
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
