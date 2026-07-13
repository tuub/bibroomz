import type postcssrc from "postcss-load-config";

export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
} satisfies postcssrc.Config;
