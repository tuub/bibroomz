import type postcssrc from "postcss-load-config";

export default {
    plugins: {
        "@tailwindcss/postcss": {},
        autoprefixer: {},
    },
} satisfies postcssrc.Config;
