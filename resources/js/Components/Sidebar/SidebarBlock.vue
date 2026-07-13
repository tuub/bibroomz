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
    <div class="rounded bg-white p-5 shadow-md dark:bg-gray-800">
        <h3 class="block text-sm font-bold uppercase">
            {{ title }}
        </h3>
        <slot />
    </div>
</template>
