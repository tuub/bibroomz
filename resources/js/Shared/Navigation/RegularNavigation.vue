<template>
    <NavigationMenu>
        <ul>
            <li v-if="isMultiTenancy">
                <NavLink icon="ri-dashboard-fill" :href="route('start')" :is-active="isPageStart">
                    {{ $t("navigation.regular.institutions") }}
                </NavLink>
            </li>
            <li v-if="institutionSlug">
                <NavLink icon="ri-home-fill" :href="route('home', { slug: institutionSlug })" :is-active="isPageHome">
                    {{ $t("navigation.regular.home", { short_title: institutionShortTitle }) }}
                </NavLink>
            </li>
            <li v-if="isPrivileged">
                <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')">
                    {{ $t("navigation.regular.admin") }}
                </NavLink>
            </li>
            <li>
                <ExternalLink icon="ri-question-fill" :href="$t('navigation.regular.help.uri')">
                    {{ $t("navigation.regular.help.text") }}
                </ExternalLink>
            </li>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
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
