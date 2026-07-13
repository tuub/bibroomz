<template>
    <div class="italic">
        {{ content.description }}
    </div>
</template>

<script setup lang="ts">
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
