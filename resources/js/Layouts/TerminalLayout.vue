<template>
    <section>
        <slot />
    </section>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;
const inertiaPage = usePage();
const { institutionShortTitle, institutionSlug, isMultiTenancy } = storeToRefs(appStore);
const { isPrivileged } = storeToRefs(authStore);

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPageStart = computed(() => {
    return inertiaPage.component === "Start";
});

const isPageHome = computed(() => {
    return inertiaPage.component === "Home";
});

const isPrivacyStatement = computed(() => {
    return inertiaPage.component === "PrivacyStatement";
});

const isSiteCredits = computed(() => {
    return inertiaPage.component === "SiteCredits";
});

</script>
