<!-- HappeningModal.vue -->
<template>
    <div class="text-3xl font-bold inline-flex">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>
    <ResourceInfo :resource="payload.resource" :is-expandable="false" :is-initially-visible="true"></ResourceInfo>
</template>

<script setup>
import ResourceInfo from "@/Components/ResourceInfo.vue";
// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    content: Object,
    payload: Object,
});
</script>
