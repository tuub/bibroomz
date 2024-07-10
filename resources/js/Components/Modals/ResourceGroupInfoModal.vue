<template>
    <div>
        <div class="mb-4 italic">
            {{ content.description }}
        </div>
    </div>
</template>

<script setup>
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
