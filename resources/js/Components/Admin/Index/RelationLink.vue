<template>
    <Link
        :href="route('admin.' + relation + '.index', params)"
        class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
    >
        {{ $t("admin." + current + "s.index.table.actions." + relation + "s") }}
    </Link>
</template>

<script setup>
defineProps({
    current: {
        type: String,
        default: null,
    },
    relation: {
        type: String,
        default: null,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});
</script>
