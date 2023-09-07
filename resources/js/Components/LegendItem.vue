<template>
    <span :class="cssClass">
        {{ label }}
    </span>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    cssClass: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        required: true,
    },
});
</script>
