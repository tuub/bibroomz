<template>
    <h1 class="text-xl">User Info</h1>
    {{ data }}
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    data: Object,
});
</script>
