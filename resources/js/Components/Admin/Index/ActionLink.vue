<template>
    <Link :href="href" class="font-medium text-red-600 hover:underline dark:text-red-500">
        <i :class="icons[action]"></i>
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
    </Link>
</template>

<script setup>
import { computed, inject } from "vue";

// eslint-disable-next-line vue/no-dupe-keys
const route = inject("ziggyRoute");

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    model: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

const href = computed(() => {
    return route(`admin.${props.model}.${props.action}`, props.params);
});

const icons = {
    edit: "ri-pencil-line",
    import: "ri-file-copy-2-line",
};
</script>
