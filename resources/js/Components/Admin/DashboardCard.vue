<script setup lang="ts">
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
            class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface m-4 w-64 border p-4 text-center shadow-sm hover:shadow-lg"
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
            class="dashboard-card m-4 max-w-sm border border-app-border bg-app-surface p-4 text-center shadow-sm dark:border-app-border dark:bg-app-surface"
        >
            <i :class="icon" class="text-2xl"></i>
            <h5 class="mb-2 mt-2 text-xl font-semibold uppercase tracking-tight text-app-text dark:text-app-text">
                {{ title }}
            </h5>
        </div>
    </Link>
    -->
</template>
