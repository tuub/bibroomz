<template>
    <div class="space-y-4">
        <div class="italic">
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
