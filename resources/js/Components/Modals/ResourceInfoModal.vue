<template>
    <ResourceInfo :resource="payload.resource" :is-expandable="false" :is-initially-visible="true" />
</template>

<script setup lang="ts">
import ResourceInfo from "@/Components/ResourceInfo.vue";
import type { ResourceInfoData } from "@/Composables/ModalActions";
import type { ModalContent } from "@/Stores/Modal";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps<{
    content: ModalContent;
    payload: { resource: ResourceInfoData };
}>();
</script>
