<template>
    <i :class="`ri-sort-${sortDirection}`"></i>
</template>

<script setup>
defineProps({
    sortDirection: {
        type: String,
        required: true,
    },
});
</script>
