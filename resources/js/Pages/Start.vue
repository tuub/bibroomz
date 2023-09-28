<template>
    <PageHead title="Start" page-type="splash" />

    <XModal />

    <div class="institution-cards-wapper">
        <InstitutionCard
            v-for="institution in institutions"
            :key="institution.id"
            :institution="institution"
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
import { onBeforeMount, onMounted } from "vue";

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
    appStore.resourceGroup = null;
    appStore.settings = null;
    appStore.hiddenDays = null;
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
