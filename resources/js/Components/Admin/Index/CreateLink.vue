<template>
    <Button icon="ri-add-circle-line" :aria-label="buttonLabel" :label="buttonLabel" @click="visitCreateRoute" />
</template>

<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { computed, inject } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        model: string;
        params?: Record<string, unknown>;
    }>(),
    {
        params: () => ({}),
    },
);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const route = inject<ZiggyRouteFn>("ziggyRoute")!;
const buttonLabel = computed(() => trans("admin." + props.model + "s.index.table.actions.create"));

// ------------------------------------------------
// Methods
// ------------------------------------------------
const visitCreateRoute = () => {
    router.visit(route("admin." + props.model + ".create", props.params));
};
</script>
