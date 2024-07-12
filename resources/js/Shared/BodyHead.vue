<template>
    <div>
        <h1 class="text-2xl font-bold">{{ title }}</h1>
        <p class="description">
            {{ description }}
        </p>
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>
