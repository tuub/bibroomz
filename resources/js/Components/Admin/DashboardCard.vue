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
<template>
    <Link :href="link">
        <div
            class="m-4 w-64 border border-gray-200 bg-white p-4 text-center shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="2xl font-bold">
                <i :class="icon" class="text-2xl"></i>
                {{ title }}
            </div>
            <div>
                <p class="m-0">
                    {{ description }}
                </p>
            </div>
        </div>
    </Link>
    <!--
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
    -->
</template>
