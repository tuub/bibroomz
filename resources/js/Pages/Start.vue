<template>
    <PageHead title="Start" page_type="splash" />

    <div class="flex flex-wrap items-center justify-center">
        <InstitutionCard v-for="institution in institutions"
                         :title="institution.title"
                         :location="institution.location"
                         :home_uri="institution.home_uri"
                         :logo_uri="institution.logo_uri"
                         :teaser_uri="institution.teaser_uri"
                         :link="route('home', {slug: institution.slug})">
        </InstitutionCard>
    </div>

</template>

<script setup>
import InstitutionCard from '../Components/InstitutionCard.vue';
import {onMounted} from "vue";
import {useAppStore} from "../Stores/AppStore";
import PageHead from "@/Shared/PageHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    institutions: Object,
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    appStore.institution = null
});
</script>
