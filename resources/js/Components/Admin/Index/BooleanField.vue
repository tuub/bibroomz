<template>
    <i v-if="isTrue" class="ri-checkbox-circle-line text-green-500"></i>
    <i v-else class="ri-close-circle-line text-red-500"></i>
</template>

<script setup>

defineProps({
    isTrue: {
        type: Boolean,
        default: false,
    },
});
</script>
