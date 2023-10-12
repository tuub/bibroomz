<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" head-key="description" />
    </Head>
    <section id="content" class="content-wrapper">
        <slot />
    </section>
    <footer id="footer" class="footer">
        <ul>
            <li>
                <a href="https://www.tu.berlin/datenschutz" target="_blank">
                    {{ $t("navigation.regular.privacy_statement") }}
                </a>
            </li>
            <li>
                <NavLink :href="route('site_credits')" :is-active="isSiteCredits">
                    {{ $t("navigation.regular.site_credits") }}
                </NavLink>
            </li>
        </ul>
    </footer>
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

window.onscroll = function(ev) {
    let footer_element = document.getElementById('footer')
    footer_element.classList.add("hide-footer");
    if ((window.innerHeight + Math.round(window.scrollY)) >= document.body.offsetHeight) {
        footer_element.classList.add("show-footer");
    }
    else {
        footer_element.classList.remove("show-footer");
    }
};
</script>
<style>
.content-wrapper {
    margin: 10em 2em 4em 2em;
    position: relative;

}

.footer {
    position: fixed;
    padding: 20px 0 20px 0;
    background-color: black;
    color: white;
    height: 0.5rem;
    width: 100%;
    bottom: 0px;
    z-index: 20;
    display: block;
}

.hide-footer {
    display: none;
}

.show-footer {
    display: block;
}

.footer > ul > li:nth-child(1) > a {
    position: absolute;
    right: 160px;
    top: 8px;
    color: white;
}

.footer > ul > li:nth-child(2) > a {
    position: absolute;
    right: 40px;
    top: 8px;
    color: white;
}
</style>
