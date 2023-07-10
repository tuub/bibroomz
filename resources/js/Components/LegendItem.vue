<template>
    <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400"
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
