<template>
    <Link :href="href" class="text-feedback-danger font-medium hover:underline">
        <i :class="icons[action]"></i>
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
    </Link>
</template>

<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { computed, inject } from "vue";

type ActionLinkAction = "edit" | "import";

const route = inject<ZiggyRouteFn>("ziggyRoute")!;

const props = withDefaults(
    defineProps<{
        action: ActionLinkAction;
        model: string;
        params?: Record<string, unknown>;
    }>(),
    {
        params: () => ({}),
    },
);

const href = computed(() => {
    return route(`admin.${props.model}.${props.action}`, props.params);
});

const icons: Record<ActionLinkAction, string> = {
    edit: "ri-pencil-line",
    import: "ri-file-copy-2-line",
};
</script>
