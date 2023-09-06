<template>
    <a target="_blank">
        <i :class="icon" class="pr-1"></i>
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
        required: true,
    },
});
</script>
