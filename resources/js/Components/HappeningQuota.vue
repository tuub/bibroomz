<template>
    <div class="">
        <span class="font-bold">{{ type }}</span>:
        <span class="font-normal">{{ value }} / {{ setting }}</span>
    </div>
</template>

<script setup>
let props = defineProps({
    type: String,
    value: Number,
    setting: Number,
})
</script>
