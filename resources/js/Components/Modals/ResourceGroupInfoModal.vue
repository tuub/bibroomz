<template>
    <div class="italic">
        {{ content.description }}
    </div>
</template>

<script setup lang="ts">
import type { ResourceGroupInfoData } from "@/Composables/ModalActions";
import type { ModalContent } from "@/Stores/Modal";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps<{
    content: ModalContent;
    payload: { resourceGroup: ResourceGroupInfoData };
}>();
</script>
