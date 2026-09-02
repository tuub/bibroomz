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
    <main
        class="bg-app-page dark:bg-app-page flex flex-1 items-stretch justify-center p-4 transition-transform duration-300"
        :aria-label="$t('accessibility.aria_label.main')"
    >
        <section
            id="content"
            class="bg-app-surface dark:bg-app-surface dark:text-app-text w-3/4 grow rounded-sm p-6 shadow-md"
        >
            <slot name="breadcrumbs" />
            <slot />
            <Toast
                position="bottom-right"
                @close="toastStore.removeToastMessage"
                @life-end="toastStore.removeToastMessage"
            />
        </section>
    </main>

    <!-- FOOTER START -->
    <Footer />
    <!-- FOOTER END -->

    <!-- MODAL START -->
    <XModal />
    <!-- MODAL END -->
</template>
