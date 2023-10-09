<template>
    <Link class="float-left" :class="{ 'font-extrabold': isActive }">
        <i v-if="icon" :class="icon" class="pr-1"></i>
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
        required: false,
    },
    isActive: {
        type: Boolean,
        default: false,
    },
});
</script>
