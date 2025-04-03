<template>
    <a class="flex" target="_blank">
        <div v-if="icon" class="mr-2"><i :class="icon"></i></div>
        <slot />
    </a>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    icon: {
        type: String,
        required: false,
        default: "default",
    },
});
</script>
