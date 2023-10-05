<!-- HappeningModal.vue -->
<template>
    <div>
        <div class="text-3xl font-bold inline-flex">
            {{ content.title }}
        </div>
        <div class="italic mt-4 mb-4">
            {{ content.description }}
        </div>
        <ResourceInfo :resource="payload.resource" :is-expandable="false" :is-initially-visible="true" />
    </div>
</template>

<script setup>
import ResourceInfo from "@/Components/ResourceInfo.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    content: {
        type: Object,
        required: true,
    },
    payload: {
        type: Object,
        required: true,
    },
});
</script>
