<template>
    <Link href="/"
          class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700"
          :class="{'font-bold underline': is_active}">
        <slot />
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    is_active: Boolean,
})
</script>
