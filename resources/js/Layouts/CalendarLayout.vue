<script setup lang="ts">
import Footer from "@/Shared/Footer.vue";
import Header from "@/Shared/Header.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useToastStore } from "@/Stores/ToastStore";
import { withBaseUrl } from "@/baseUrl";

import { usePage } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { onBeforeMount, onMounted, onUnmounted } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;
const page = usePage();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const scrollToSidebar = () => {
    const element = document.getElementById("sidebar");
    if (element) {
        element.scrollIntoView({
            block: "start",
            behavior: "smooth", // smooth scroll
        });
    }
};

// ------------------------------------------------
// Hooks
// ------------------------------------------------
onBeforeMount(() => {
    void authStore.check();
    appStore.setGlobalSystemNotification(page.props.systemNotification);
});
onMounted(() => {
    toastStore.initToast();
});
onUnmounted(() => {
    authStore.unsubscribe();
});
</script>
<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" />
        <link rel="icon" type="image/x-icon" :href="withBaseUrl('/favicon.ico')" />
    </Head>

    <!-- HEADER START -->
    <Header />
    <!-- HEADER END -->

    <!-- MAIN CONTENT START -->
    <div
        class="flex flex-wrap items-stretch justify-center bg-app-page p-4 transition-transform duration-300 lg:flex-nowrap dark:bg-app-page"
    >
        <main
            id="content"
            class="m-2 w-full rounded bg-app-surface p-5 shadow-md lg:w-3/5 xl:w-3/4 dark:bg-app-surface dark:text-app-text"
        >
            <div class="flex justify-center">
                <Button
                    class="mb-2 text-xs lg:hidden"
                    severity="contrast"
                    size="small"
                    icon="pi pi-arrow-circle-down"
                    :label="$t('navigation.jump_to_sidebar')"
                    @click="scrollToSidebar"
                />
            </div>
            <slot />
            <Toast
                position="bottom-right"
                @close="toastStore.removeToastMessage"
                @life-end="toastStore.removeToastMessage"
            />
        </main>
        <!-- MAIN CONTENT END -->
        <!-- SIDEBAR CONTENT START -->
        <aside
            id="sidebar"
            class="order-last m-2 w-full rounded bg-app-surface p-5 shadow-md md:order-none lg:w-2/5 xl:w-1/4 dark:bg-app-surface dark:text-app-text"
        ></aside>
        <!-- SIDEBAR CONTENT END -->
    </div>

    <!-- FOOTER START -->
    <Footer />
    <!-- FOOTER END -->

    <!-- MODAL START -->
    <XModal />
    <!-- MODAL END -->
</template>
