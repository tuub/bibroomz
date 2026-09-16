<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: "",
    },
);
</script>

<template>
    <div class="bg-app-surface dark:bg-app-surface rounded-sm p-5 shadow-md">
        <h3 class="block text-sm font-bold uppercase">
            {{ title }}
        </h3>
        <slot />
    </div>
</template>
