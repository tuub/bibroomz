<template>
    <div v-text="message" class="text-red-500 text-sm mt-1"></div>
</template>

<script setup>
let props = defineProps({
    message: String,
})
</script>
