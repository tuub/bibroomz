<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" head-key="description" />
    </Head>
    <section id="content" class="content-wrapper">
        <slot />
        <footer class="footer">
            <ul>
                <li>
                    <NavLink
                        icon="ri-government-fill"
                        :href="route('privacy_statement')"
                        :is-active="isPrivacyStatement"
                    >
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
    </section>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
import { useAppStore } from "@/Stores/AppStore";

import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;
const inertiaPage = usePage();

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPrivacyStatement = computed(() => {
    return inertiaPage.component === "PrivacyStatement";
});

const isSiteCredits = computed(() => {
    return inertiaPage.component === "SiteCredits";
});
</script>
<style>
.content-wrapper {
    margin: 10em 2em 0 2em;
    min-height: 80vh;
    position: relative;
}

.footer {
    padding: 20px 0 20px 0;
    background-color: black;
    color: white;
    position: absolute;
    bottom: -80px;
    right: -32px;
    width: 130%;
    height: 0.5rem;
}

.footer > ul > li:nth-child(1) > a {
    position: absolute;
    right: 160px;
    top: 8px;
}

.footer > ul > li:nth-child(2) > a {
    position: absolute;
    right: 40px;
    top: 8px;
}
</style>
