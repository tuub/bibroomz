<template>
    <Button
        v-if="hasPermission('create_' + model + 's')"
        icon="ri-add-circle-line"
        :aria-label="buttonLabel"
        :label="buttonLabel"
        @click="visitCreateRoute"
    />
</template>

<script setup lang="ts">
import { useAuthStore } from "@/Stores/AuthStore";

import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import { computed, inject } from "vue";
import type { route as ZiggyRoute } from "ziggy-js";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    model: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const route = inject<typeof ZiggyRoute>("route");
const { hasPermission } = authStore;
const buttonLabel = computed(() => trans("admin." + props.model + "s.index.table.actions.create"));

// ------------------------------------------------
// Methods
// ------------------------------------------------
const visitCreateRoute = () => {
    router.visit(route("admin." + props.model + ".create", props.params));
};
</script>
