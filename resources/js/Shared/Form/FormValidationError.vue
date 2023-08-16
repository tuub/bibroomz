<template>
    <div class="text-red-500 text-sm mt-1" v-text="message"></div>
</template>

<script setup>
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
