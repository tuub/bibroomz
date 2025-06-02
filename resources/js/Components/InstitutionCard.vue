<script setup>
import { useAppStore } from "@/Stores/AppStore";

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
<template>
    <Card class="mx-10 my-5 flex max-w-full md:max-w-96">
        <template #header>
            <img class="mx-auto mt-5 w-96" :src="institution.teaser_uri" alt="Teaser Image" />
        </template>
        <template #title>
            <div class="text-center text-base font-bold lg:text-sm">
                <a :href="institution.home_uri" target="_blank">
                    <!--<img :src="institution.logo_uri" class="w-1/2 mx-auto" />-->
                    {{ translate(institution.title) }}
                </a>
            </div>
        </template>
        <template #subtitle>
            <div class="text-center text-base lg:text-sm">
                <i class="ri-map-pin-fill pr-1"></i> {{ institution.location }}
            </div>
        </template>
        <template #content>
            <p class="m-0">
                {{ translate(institution.description) }}
            </p>
        </template>
        <template #footer>
            <div class="flex flex-wrap justify-center gap-2">
                <Button
                    v-for="resource_group in institution.resource_groups"
                    :key="resource_group.id"
                    :label="translate(resource_group.title)"
                    :href="
                        route('home', { institution_slug: institution.slug, resource_group_slug: resource_group.slug })
                    "
                    severity="secondary"
                    outlined
                    class="text-base text-tub hover:bg-tub hover:text-white md:text-sm"
                    @click="
                        $inertia.get(
                            route('home', {
                                institution_slug: institution.slug,
                                resource_group_slug: resource_group.slug,
                            }),
                        )
                    "
                />
            </div>
        </template>
    </Card>
</template>
