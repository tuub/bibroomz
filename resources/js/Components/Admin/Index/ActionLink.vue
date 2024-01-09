<template>
    <Link
        :href="route('admin.' + model + '.' + action, params)"
        :method="method"
        :as="method === 'get' ? 'a' : 'button'"
        class="font-medium text-red-600 dark:text-red-500 hover:underline"
    >
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
    </Link>
</template>

<script setup>
defineProps({
    action: {
        type: String,
        default: null,
    },
    model: {
        type: String,
        default: null,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    method: {
        type: String,
        default: "get",
    },
});
</script>
