<template>
    <Link :href="href" class="text-feedback-danger font-medium hover:underline">
        <i :class="icons[relation]" />
        {{ $t("admin." + current + "s.index.table.actions." + relation + "s") }}
    </Link>
</template>

<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { computed, inject } from "vue";

type RelationLinkRelation = "closing" | "mail" | "resource" | "resource_group" | "setting" | "user";

// eslint-disable-next-line vue/no-dupe-keys
const route = inject<ZiggyRouteFn>("ziggyRoute")!;

const props = withDefaults(
    defineProps<{
        current: string;
        relation: RelationLinkRelation;
        params?: Record<string, unknown>;
        route?: string | null;
    }>(),
    {
        params: () => ({}),
        route: null,
    },
);

const href = computed(() => {
    return route(props.route ?? `admin.${props.relation}.index`, props.params);
});

const icons: Record<RelationLinkRelation, string> = {
    closing: "ri-calendar-close-fill",
    mail: "ri-mail-line",
    resource: "ri-calendar-line",
    resource_group: "ri-folder-4-fill",
    setting: "ri-settings-5-fill",
    user: "ri-group-line",
};
</script>
