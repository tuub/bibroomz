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
        default: "",
    },
    severity: {
        type: String,
        required: false,
        default: "info",
    },
});
</script>
<template>
    <Tag :severity="severity" :value="label" class="p-1 text-xs uppercase"></Tag>
</template>
