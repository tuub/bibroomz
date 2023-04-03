<template>
    <div v-for="message in messages" v-text="message" class="text-red-500 text-sm mt-1"></div>
</template>

<script setup>
let props = defineProps({
    messages: Array
})

console.log(props)
</script>
