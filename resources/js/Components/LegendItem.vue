<template>
    <span class=""
         :class="cssClass">
        {{ label }}
    </span>

</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    cssClass: String,
    label: String,
})
</script>
