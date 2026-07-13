<template>
    <ResourceInfo :resource="payload.resource" :is-expandable="false" :is-initially-visible="true" />
</template>

<script setup lang="ts">
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
