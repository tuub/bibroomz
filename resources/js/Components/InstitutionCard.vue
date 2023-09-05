<template>
    <div class="institution-card-wapper text-center max-w-sm p-4 m-4 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <img :src="logo_uri" class="h-14 mx-auto" />
        <Link :href="link">
            <h1 class="mt-2 mb-2 text-medium font-normal tracking-tight text-gray-900 dark:text-white uppercase">
                {{ title }}
            </h1>
            <img :src="teaser_uri" />
        </Link>
        <p class="py-2">
            <i class="ri-map-pin-fill pr-1"></i> {{ location }}
        </p>
        <p class="py-2">
            <i class="ri-external-link-line pr-1"></i>
            <a :href="home_uri" target="_blank">
                {{ home_uri }}
            </a>
        </p>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    title: String,
    location: String,
    home_uri: String,
    logo_uri: String,
    teaser_uri: String,
    link: String,
});
</script>
<style>
    .institution-card-wapper{
        margin: auto;
        margin-bottom: 30px;
        width: 100%;
    }
</style>