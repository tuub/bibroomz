<template>
    <div
        class="institution-card-wapper m-4 rounded-lg border border-gray-200 bg-white p-4 text-center shadow dark:border-gray-700 dark:bg-gray-800"
    >
        <a :href="institution.home_uri" target="_blank">
            <img :src="institution.logo_uri" class="institution-logo" alt="institution-logo" />
        </a>
        <h1 class="text-medium mb-2 mt-2 font-normal uppercase tracking-tight text-gray-900 dark:text-white">
            {{ translate(institution.title) }}
        </h1>
        <img class="teaser-img" :src="institution.teaser_uri" alt="teaser-img" />
        <p class="py-2"><i class="ri-map-pin-fill pr-1"></i> {{ institution.location }}</p>
        <Link
            v-for="resource_group in institution.resource_groups"
            :key="resource_group.id"
            :href="
                route('home', {
                    institution_slug: institution.slug,
                    resource_group_slug: resource_group.slug,
                })
            "
        >
            <div class="institution-resource-groups-button">
                {{ translate(resource_group.title) }}
            </div>
        </Link>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

import { Link } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    institution: {
        type: Object,
        required: true,
    },
});

const appStore = useAppStore();
const translate = appStore.translate;
</script>
<style>
.institution-card-wapper {
    float: left;
    margin: -5px 25px 30px auto;
    box-shadow: 0 3px 3px rgb(204, 203, 203);
    padding: 40px;
}

.institution-card-wapper > h1 {
    display: none;
}

.institution-logo {
    margin: auto;
    padding: 20px;
    height: 100px;
}

.teaser-img {
    margin: auto;
    width: 600px;
}

.institution-resource-groups-button {
    rotate: unset;
    z-index: 9;
    transition:
        background 0.25s,
        color 0.25s;
    margin: 0.7em auto;
    box-shadow: 0 1px 1px rgb(204, 203, 203);
    background: #c40d1e;
    padding: 0.7em;
    width: 100%;
    min-width: 2rem;
    max-width: 600px;
    min-height: 50px;
    max-height: 200px;
    overflow: hidden;
    color: #ffffff;
    font-weight: 400;
    font-size: 1.2rem;
    font-family: Muli, sans-serif, Arial;
    text-align: center;
    text-decoration: none;
}

@media only screen and (max-width: 1200px) {
    .institution-resource-groups-button {
        width: 100%;
    }
}

@media only screen and (max-width: 400px) {
    .institution-logo {
        margin: auto;
        padding: 20px;
        height: 80px;
    }

    .institution-resource-groups-button {
        width: 100%;
        font-size: 0.8rem;
    }

    .institution-card-wapper:nth-child(2) {
        margin: -5px 0px 35px auto;
        padding: 40px;
    }
}
</style>
