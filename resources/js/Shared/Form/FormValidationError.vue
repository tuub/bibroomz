<template>
    <div class="mt-1 text-sm text-feedback-danger" v-text="message"></div>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    message: {
        type: String,
        default: "",
    },
});
</script>
