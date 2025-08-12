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
        <h3 class="block text-sm font-bold uppercase">
            {{ title }}
        </h3>
        <slot />
    </div>
</template>
