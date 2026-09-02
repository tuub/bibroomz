<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: false,
        default: "default",
    },
});
</script>

<template>
    <Tag :value="label" class="no-wrap px-2 text-xs tracking-wider uppercase"></Tag>
</template>
