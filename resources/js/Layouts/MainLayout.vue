<script setup>
import Footer from "@/Shared/Footer.vue";
import Header from "@/Shared/Header.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useToastStore } from "@/Stores/ToastStore";

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
const baseUrl = import.meta.env.VITE_API_URL;

// ------------------------------------------------
// Hooks
// ------------------------------------------------
onBeforeMount(() => {
    authStore.check();
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
        <!--<link rel="icon" type="image/x-icon" :href="`${baseUrl}/images/1797769.png`" />-->
        <link rel="icon" type="image/x-icon" :href="`${baseUrl}/favicon.ico`" />
    </Head>

    <!-- HEADER START -->
    <Header />
    <!-- HEADER END -->

    <!-- MAIN CONTENT START -->
    <main
        class="flex flex-1 items-stretch justify-center bg-gray-100 p-4 transition-transform duration-300"
        :aria-label="$t('accessibility.aria_label.main')"
    >
        <section id="content" class="w-3/4 flex-grow rounded bg-white p-6 shadow-md">
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
