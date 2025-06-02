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
    type: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <button class="flex items-center justify-center rounded border border-black p-1 hover:bg-tub hover:text-white">
        <i v-if="icon" class="text-xs" :class="icon" :title="label"></i>
        <span class="ml-1 text-xs">{{ label }}</span>
    </button>
</template>
