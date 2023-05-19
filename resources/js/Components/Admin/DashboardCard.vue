<template>
    <div class="text-center max-w-sm p-4 m-4 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <i :class="icon" class="text-2xl"></i>
        <Link :href="link">
            <h5 class="mt-2 mb-2 text-xl font-semibold tracking-tight text-gray-900 dark:text-white uppercase">
                {{ title }}
            </h5>
        </Link>
        <p class="mb-3 font-normal text-gray-500 dark:text-gray-400">
            {{ content }}
        </p>
        <Link :href="link" class="btn inline-flex items-center text-black hover:text-white bg-gray-200">
            <i class="ri-tools-fill">Administer</i>
        </Link>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    title: String,
    icon: String,
    content: String,
    link: String,
});

</script>
