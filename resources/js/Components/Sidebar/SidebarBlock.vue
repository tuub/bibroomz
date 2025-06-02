<script setup>
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
    <div class="rounded bg-white p-5 shadow-md">
        <h2 class="block text-sm font-bold uppercase">
            {{ title }}
        </h2>
        <slot />
    </div>
</template>
