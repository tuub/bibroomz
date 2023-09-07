<template>
    <PageHead title="Start" page-type="splash" />

    <XModal />

    <div class="">
        <InstitutionCard
            v-for="institution in institutions"
            :key="institution.id"
            :title="institution.title"
            :location="institution.location"
            :home_uri="institution.home_uri"
            :logo_uri="institution.logo_uri"
            :teaser_uri="institution.teaser_uri"
            :link="route('home', { slug: institution.slug })"
        >
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
defineProps({
    institutions: {
        type: Array,
        default: () => [],
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const modal = useModal();

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.institution = null;
});

onMounted(() => {
    modal.init(
        new FlowbiteModal(document.getElementById("modal"), {
            closable: true,
            placement: "center",
            backdrop: "dynamic",
            backdropClasses: "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                modal.cleanup();
            },
        }),
    );
});
</script>
