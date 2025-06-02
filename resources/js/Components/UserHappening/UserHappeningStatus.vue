<script setup>
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
    <Tag :value="label" class="no-wrap px-2 text-xs uppercase tracking-wider"></Tag>
</template>
