<!-- HappeningModal.vue -->
<template>
    <div class="text-3xl font-bold inline-flex">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>
    <div class="font-bold">{{ $t('modal.resource_info.resource_capacity') }}</div>
    <div class="mb-2">{{ payload.resource.extendedProps.capacity }}</div>
    <div class="font-bold">{{ $t('modal.resource_info.resource_description') }}</div>
    <div class="mb-2">{{ payload.resource.extendedProps.description }}</div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    content: Object,
    payload: Object,
});
</script>
