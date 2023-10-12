<template>
    <div
        class="institution-card-wapper text-center p-4 m-4 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700"
    >
        <img :src="institution.logo_uri" class="institution-logo" />
        <h1 class="mt-2 mb-2 text-medium font-normal tracking-tight text-gray-900 dark:text-white uppercase">
            {{ translate(institution.title) }}
        </h1>
        <img class="teaser-img" :src="institution.teaser_uri" />
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
                    {{ translate(resource_group.name) }}
            </div>
        </Link>
        <p class="py-2">
            <i class="ri-external-link-line pr-1"></i>
            <a :href="institution.home_uri" target="_blank">
                {{ institution.home_uri }}
            </a>
        </p>
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
    margin: -5px 30px 35px auto;
    padding: 40px;
    width: 47.8%;
    box-shadow: 0 3px 3px rgb(204, 203, 203);
    float: left;
    border-radius: 0px;
}

.institution-card-wapper:nth-child(2) {
    margin: -5px 0px 35px auto;
    padding: 40px;
}

.institution-card-wapper > h1 {
    display: none;
}

.institution-logo {
    padding: 20px;
    height: 100px;
    margin: auto;
}

.teaser-img {
    margin: auto;
}

.institution-resource-groups-button {
    margin: 0.5em 0em;
    padding: 0.5em;
    rotate: unset;
    height: 50px;
    width: 100%;
    z-index: 9;
    background: #C40D1E;
    color: #FFFFFF;
    font-family: Muli, sans-serif, Arial;
    font-size: 1.2rem;
    font-weight: 400;
    min-width: 2rem;
    text-align: center;
    text-decoration: none;
    transition:
        background 0.25s,
        color 0.25s;
    box-shadow: 0 1px 1px rgb(204, 203, 203);
}

@media only screen and (max-width: 1200px) {
    .institution-card-wapper {
        width: 100%;
    }

    .institution-resource-groups-button {
        width: 100%;
    }
}

@media only screen and (max-width: 400px) {
    .institution-logo {
        padding: 20px;
        height: 80px;
        margin: auto;
    }
}
</style>
