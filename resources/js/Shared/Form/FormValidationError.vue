<template>
    <div class="mt-1 text-sm text-red-500" v-text="message"></div>
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
