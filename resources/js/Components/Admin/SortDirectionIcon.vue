<template>
    <i :class="`ri-sort-${sortDirection}`"></i>
</template>

<script setup lang="ts">
defineProps<{
    sortDirection: "asc" | "desc";
}>();
</script>
