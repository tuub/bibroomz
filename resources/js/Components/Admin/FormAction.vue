<template>
    <div class="mb-6 space-x-1">
        <button
            type="button"
            class="rounded bg-feedback-success-soft px-4 py-2 text-feedback-success-contrast hover:bg-feedback-success-hover"
            :disabled="form.processing"
            @click="submitForm"
        >
            {{ $t("admin.general.form.submit") }}
        </button>

        <button
            type="button"
            class="rounded bg-button-secondary px-4 py-2 text-button-secondary-contrast hover:bg-button-secondary-hover"
            @click="cancelForm"
        >
            {{ $t("admin.general.form.cancel") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { router } from "@inertiajs/vue3";
import { inject } from "vue";

type FormActionForm = {
    id?: number | string;
    processing?: boolean;
    post: (url: string) => void;
};

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        form: FormActionForm;
        model: string;
        action?: string | null;
        routeParams?: Record<string, unknown>;
        cancelRoute: string;
        cancelRouteParams?: Record<string, unknown>;
    }>(),
    {
        action: null,
        routeParams: () => ({}),
        cancelRouteParams: () => ({}),
    },
);

const route = inject<ZiggyRouteFn>("ziggyRoute")!;

const submitForm = () => {
    const action = props.action ?? (props.form.id ? "update" : "store");
    props.form.post(route(`admin.${props.model}.${action}`, props.routeParams));
};

const cancelForm = () => {
    router.visit(route(props.cancelRoute, props.cancelRouteParams));
};
</script>
