<template>
    <Link :href="link">
        <div
            class="dashboard-card text-center max-w-sm p-4 m-4 bg-white border border-gray-200 shadow dark:bg-gray-800 dark:border-gray-700"
        >
            <i :class="icon" class="text-2xl"></i>
            <h5 class="mt-2 mb-2 text-xl font-semibold tracking-tight text-gray-900 dark:text-white uppercase">
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
<style>
.dashboard-card{
    width: 500px;
    height: 150px;
    display: inline-block;
    overflow: hidden;
    margin: 0px 0px 20px 20px;
    float: left;
    padding: 40px 20px 20px 20px;
    text-align: center;
}

</style>
