<template>
    <div :class="cssClass" class="rounded p-1 pl-10 uppercase text-xs mr-2">
        {{ label }}
    </div>
</template>

<script setup>
let props = defineProps({
    cssClass: String,
    label: String,
})
</script>

<style>

</style>
