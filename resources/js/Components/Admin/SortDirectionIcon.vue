<template>
    <i :class="`ri-sort-${sortDirection}`"></i>
</template>

<script setup lang="ts">
defineProps({
    sortDirection: {
        type: String,
        required: true,
    },
});
</script>
