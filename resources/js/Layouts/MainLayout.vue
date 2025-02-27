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
            @close="toastStore.removeToastMessage"
            @life-end="toastStore.removeToastMessage"
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

// ------------------------------------------------
// Hooks
// ------------------------------------------------
onMounted(() => {
    toastStore.initToast();
});

/* Show footer when user scrolls to bottom of page. */
window.addEventListener("scroll", function () {
    const footerElement = document.getElementById("footer");

    if (window.innerHeight + Math.round(window.scrollY) >= document.body.offsetHeight) {
        footerElement.classList.remove("hide-footer");
        footerElement.classList.add("show-footer");
    } else {
        footerElement.classList.remove("show-footer");
        footerElement.classList.add("hide-footer");
    }
});
</script>

<style>
.footer {
    display: block;
    position: fixed;
    bottom: 0px;
    z-index: 20;
    background-color: black;
    padding: 20px 0 20px 0;
    width: 100%;
    height: 0.5rem;
    color: white;
}

.hide-footer {
    display: none;
}

.show-footer {
    display: block;
}

.footer > ul > li:nth-child(1) > a {
    position: absolute;
    top: 8px;
    right: 140px;
    color: white;
}

.footer > ul > li:nth-child(2) > a {
    position: absolute;
    top: 8px;
    right: 30px;
    color: white;
}
</style>
