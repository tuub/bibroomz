<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    title: {
        type: String,
        required: false,
        default: "",
    },
});
</script>

<template>
    <div class="rounded bg-app-surface p-5 shadow-md dark:bg-app-surface">
        <h3 class="block text-sm font-bold uppercase">
            {{ title }}
        </h3>
        <slot />
    </div>
</template>
