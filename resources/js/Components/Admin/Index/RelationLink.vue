<template>
    <Link
        :href="route('admin.' + relation + '.index', params)"
        class="font-medium text-red-600 hover:underline dark:text-red-500"
    >
        <i v-if="relation === 'setting'" class="ri-settings-5-fill"></i>
        <i v-if="relation === 'closing'" class="ri-calendar-close-fill"></i>
        <i v-if="relation === 'resource'" class="ri-calendar-line"></i>
        <i v-if="relation === 'mail'" class="ri-mail-line"></i>
        {{ $t("admin." + current + "s.index.table.actions." + relation + "s") }}
    </Link>
</template>

<script setup>
defineProps({
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
});
</script>
