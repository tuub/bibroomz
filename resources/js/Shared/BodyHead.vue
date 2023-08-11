<template>
    <div>
        <h1 class="font-bold text-2xl">{{ title }}</h1>
        <p class="description">
            {{ description }}
        </p>
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    title: String,
    description: String,
})
</script>
