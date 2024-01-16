<template>
    <PageHead page-type="splash" />

    <XModal />

    <div class="institution-cards-wapper">
        <InstitutionCard v-for="institution in institutions" :key="institution.id" :institution="institution">
        </InstitutionCard>
    </div>
</template>

<script setup>
import InstitutionCard from "@/Components/InstitutionCard.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";

import { onBeforeMount } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
    institutions: {
        type: Array,
        default: () => [],
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.appName = props.appName;
    appStore.resourceGroup = null;
    appStore.settings = null;
    appStore.hiddenDays = null;
});
</script>
<style>
.institution-cards-wapper {
    display: flow-root;
}
</style>
