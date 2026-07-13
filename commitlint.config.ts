import type { UserConfig } from "@commitlint/types";

export default {
    extends: ["@commitlint/config-conventional"],
    rules: {
        "body-max-line-length": [0],
    },
} satisfies UserConfig;
