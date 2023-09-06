<template>
    <Link class="float-left"
          :class="{'font-extrabold': is_active}">
        <i :class="icon" class="pr-1"></i>
        <slot />
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    icon: String,
    is_active: Boolean,
})
</script>
