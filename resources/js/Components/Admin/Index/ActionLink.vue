<template>
    <Link :href="route('admin.' + model + '.' + action, params)"
          class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
        {{ $t('admin.' + model + 's.index.table.actions.' + action) }}
    </Link>
</template>

<script setup>
const props = defineProps({
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
});
</script>
