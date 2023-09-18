<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" head-key="description" />
    </Head>
    <section id="content" class="content-wrapper">
        <slot />
    </section>
    <footer class="footer">
        <ul>
            <li>
                <NavLink icon="ri-government-fill" :href="route('privacy_statement')" :is-active="isPrivacyStatement">
                    {{ $t("navigation.regular.privacy_statement") }}
                </NavLink>
            </li>
            <li>
                <NavLink icon="ri-copyright-fill" :href="route('site_credits')" :is-active="isSiteCredits">
                    {{ $t("navigation.regular.site_credits") }}
                </NavLink>
            </li>
        </ul>
    </footer>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;






import NavLink from "@/Shared/NavLink.vue";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
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
<style>
.content-wrapper {
    margin: 8em 2em 0 2em;
}
.footer{
    width: 100%;
    height: 100px;
    padding: 40px;
    margin-top: 100px;
    background-color: black;
    color: white;
}
</style>
