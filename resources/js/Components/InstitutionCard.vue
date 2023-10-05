<template>
    <div
        class="institution-card-wapper text-center p-4 m-4 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700"
    >
        <img :src="institution.logo_uri" class="h-14 mx-auto" />
        <h1 class="mt-2 mb-2 text-medium font-normal tracking-tight text-gray-900 dark:text-white uppercase">
            {{ translate(institution.title) }}
        </h1>
        <img class="teaser-img" :src="institution.teaser_uri" />
        <p class="py-2"><i class="ri-map-pin-fill pr-1"></i> {{ institution.location }}</p>
        <p v-for="resource_group in institution.resource_groups" :key="resource_group.id">
            <Link
                :href="
                    route('home', {
                        institution_slug: institution.slug,
                        resource_group_slug: resource_group.slug,
                    })
                "
            >
                {{ translate(resource_group.name) }}
            </Link>
        </p>
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
    margin: auto;
    margin-top: 30px;
    margin-right: 30px;
    width: 30%;
    box-shadow: 0 3px 3px rgb(204, 203, 203);
    float: left;
}

.institution-card-wapper:last-child {
    margin-right: 0;
}

.teaser-img {
    margin: auto;
}

@media only screen and (max-width: 1150px) {
    .institution-card-wapper {
        width: 100%;
    }
}
</style>
