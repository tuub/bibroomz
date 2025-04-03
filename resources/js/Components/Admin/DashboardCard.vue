<template>
    <Link :href="link">
        <div
            class="dashboard-card m-4 max-w-sm border border-gray-200 bg-white p-4 text-center shadow dark:border-gray-700 dark:bg-gray-800"
        >
            <i :class="icon" class="text-2xl"></i>
            <h5 class="mb-2 mt-2 text-xl font-semibold uppercase tracking-tight text-gray-900 dark:text-white">
                {{ title }}
            </h5>
        </div>
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    link: {
        type: String,
        required: true,
    },
});
</script>
