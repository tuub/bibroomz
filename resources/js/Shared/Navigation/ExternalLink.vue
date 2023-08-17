<template>
    <a class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700"
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
