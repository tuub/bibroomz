<template>
    <Link class="flex" :class="{ 'font-extrabold': isActive }">
        <div v-if="icon" class="mr-2"><i :class="icon"></i></div>
        <slot />
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    icon: {
        type: String,
        default: null,
    },
    isActive: {
        type: Boolean,
        default: false,
    },
});
</script>
