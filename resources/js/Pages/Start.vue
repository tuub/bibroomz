<template>
    <PageHead title="Start" page-type="splash" />

    <XModal />

    <div class="institution-cards-wapper">
        <InstitutionCard
            v-for="institution in institutions"
            :key="institution.id"
            :title="translate(institution.title)"
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
import InstitutionCard from "@/Components/InstitutionCard.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import useModal from "@/Stores/Modal";

import { Modal as FlowbiteModal } from "flowbite";
import { inject, onBeforeMount, onMounted } from "vue";

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

const translate = inject("translate");

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
<style>
    .institution-cards-wapper{
        display: flow-root;
    }

</style>
