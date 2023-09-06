<template>
    <a class=""
       target="_blank">
       <i :class="icon" class="pr-1"></i>
        <slot />
    </a>
</template>

<script setup>

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    icon: String,
})
</script>
