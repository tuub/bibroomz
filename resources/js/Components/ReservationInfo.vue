<template>
    <h1 class="text-xl">Reservation Info</h1>
    <div>
        {{ data }}
    </div>
</template>

<script setup>
defineProps({
    data: Object,
});
</script>
