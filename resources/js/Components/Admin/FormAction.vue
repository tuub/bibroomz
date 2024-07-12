<template>
    <div class="mb-6">
        <button
            type="button"
            class="rounded bg-green-400 px-4 py-2 text-white hover:bg-green-700"
            :disabled="form.processing"
            @click="submitForm"
        >
            {{ $t("admin.general.form.submit") }}
        </button>
        <button type="button" class="rounded bg-gray-400 px-4 py-2 text-black hover:bg-gray-700" @click="cancelForm">
            {{ $t("admin.general.form.cancel") }}
        </button>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { inject } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    form: {
        type: Object,
        default: () => null,
    },
    model: {
        type: String,
        default: null,
    },
    cancelRoute: {
        type: String,
        default: null,
    },
    cancelRouteParams: {
        type: Object,
        default: () => ({}),
    },
});

const route = inject("ziggyRoute");

const submitForm = () => {
    if (props.form.id) {
        props.form.post(route("admin." + props.model + ".update"));
    } else {
        props.form.post(route("admin." + props.model + ".store"));
    }
};

const cancelForm = () => {
    router.visit(route(props.cancelRoute, props.cancelRouteParams));
};
</script>
