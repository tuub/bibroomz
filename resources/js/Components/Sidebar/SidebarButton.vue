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
    type: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <button
        class="border-app-text hover:bg-tub hover:text-brand-contrast dark:border-app-border flex items-center justify-center rounded-sm border p-1"
    >
        <i v-if="icon" class="text-xs" :class="icon" :title="label"></i>
        <span class="ml-1 text-xs">{{ label }}</span>
    </button>
</template>
