<template>
    <a class="flex" target="_blank">
        <div v-if="icon" class="mr-2"><i :class="icon"></i></div>
        <slot />
    </a>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    icon: {
        type: String,
        required: false,
        default: "",
    },
});
</script>
