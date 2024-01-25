<template>
    <Link
        :href="route('admin.' + model + '.' + action, params)"
        class="font-medium text-red-600 dark:text-red-500 hover:underline"
    >
        <i v-if="action === 'edit'" class="ri-pencil-line"></i>
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
    </Link>
</template>

<script setup>
defineProps({
    action: {
        type: String,
        required: true,
        validator: (value) => ["edit"].includes(value),
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
</script>
