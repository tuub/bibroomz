<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" head-key="description" />
        <link rel="icon" type="image/x-icon" :href="`${baseUrl}/images/1797769.png`" />
    </Head>
    <section id="content" class="content-wrapper">
        <slot />

        <Toast
            position="bottom-right"
            @close="toastStore.removeQuotaToastMessage"
            @life-end="toastStore.removeQuotaToastMessage"
        />
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
import { useToastStore } from "@/Stores/ToastStore";

import { usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { computed, onMounted } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const toastStore = useToastStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;
const baseUrl = import.meta.env.VITE_API_URL;
const inertiaPage = usePage();

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isSiteCredits = computed(() => {
    return inertiaPage.component === "SiteCredits";
});

window.onscroll = function () {
    let footer_element = document.getElementById("footer");
    footer_element.classList.add("hide-footer");
    if (window.innerHeight + Math.round(window.scrollY) >= document.body.offsetHeight) {
        footer_element.classList.add("show-footer");
    } else {
        footer_element.classList.remove("show-footer");
    }
};

// ------------------------------------------------
// Hooks
// ------------------------------------------------
onMounted(() => {
    toastStore.initToast();
});
</script>

<style>
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
    right: 140px;
    top: 8px;
    color: white;
}

.footer > ul > li:nth-child(2) > a {
    position: absolute;
    right: 30px;
    top: 8px;
    color: white;
}
</style>
