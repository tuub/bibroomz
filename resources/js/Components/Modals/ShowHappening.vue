<!-- ShowHappening.vue -->
<template>
    <div class="text-3xl font-bold">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>
    <HappeningInfo :happening="payload"></HappeningInfo>
</template>

<script setup>
import HappeningInfo from "../HappeningInfo.vue";

const props = defineProps({
    content: Object,
    payload: Object
})
</script>
