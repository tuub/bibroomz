<template>
    <div class="text-feedback-danger mt-1 text-sm" v-text="message"></div>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
withDefaults(
    defineProps<{
        message?: string;
    }>(),
    {
        message: "",
    },
);
</script>
