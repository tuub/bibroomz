<template>
    <Link
        :href="route('admin.' + model + '.' + action, params)"
        class="font-medium text-red-600 hover:underline dark:text-red-500"
    >
        <i :class="icons[action]"></i>
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
    </Link>
</template>

<script setup>
defineProps({
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

const icons = {
    edit: "ri-pencil-line",
    import: "ri-file-copy-2-line",
};
</script>
