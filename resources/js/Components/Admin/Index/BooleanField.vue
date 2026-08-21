<template>
    <i v-if="isTrue" class="ri-checkbox-circle-line text-feedback-success"></i>
    <i v-else class="ri-close-circle-line text-feedback-danger"></i>
</template>

<script setup lang="ts">
defineProps({
    isTrue: {
        type: Boolean,
        default: false,
    },
});
</script>
