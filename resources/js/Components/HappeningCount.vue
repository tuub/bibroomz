<template>
    <div class="bg-gray-200 p-2">
        <p class="italic">{{ count }} reservations</p>
    </div>
</template>

<script setup>
let props = defineProps({
    count: Number,
});
</script>
