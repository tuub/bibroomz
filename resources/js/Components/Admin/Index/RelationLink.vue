<template>
    <Link :href="href" class="font-medium text-red-600 hover:underline dark:text-red-500">
        <i :class="icons[relation]" />
        {{ $t("admin." + current + "s.index.table.actions." + relation + "s") }}
    </Link>
</template>

<script setup>
import { inject } from "vue";

// eslint-disable-next-line vue/no-dupe-keys
const route = inject("ziggyRoute");

const props = defineProps({
    current: {
        type: String,
        required: true,
    },
    relation: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    route: {
        type: String,
        default: null,
    },
});

const icons = {
    closing: "ri-calendar-close-fill",
    mail: "ri-mail-line",
    resource: "ri-calendar-line",
    setting: "ri-settings-5-fill",
    user: "ri-group-line",
};

const href = route(props.route ?? `admin.${props.relation}.index`, props.params);
</script>
