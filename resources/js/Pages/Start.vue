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
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
}

@media only screen and (max-width: 1400px) {
    .institution-cards-wapper {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
    }
}

@media only screen and (max-width: 1200px) {
    .institution-cards-wapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
}

@media only screen and (max-width: 800px) {
    .institution-cards-wapper {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>
