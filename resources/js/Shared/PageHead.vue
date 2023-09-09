<template>
    <Head>
        <title>{{ title ? `${title} - ${appName}` : `${appName}` }}</title>
    </Head>

    <section id="header" class="w-full">
        <BaseNavigation>
            <RegularNavigation v-if="isRegularPage"></RegularNavigation>
            <AdminNavigation v-else-if="isAdminPage"></AdminNavigation>
            <SplashNavigation v-else-if="isSplashPage"></SplashNavigation>
        </BaseNavigation>
    </section>
</template>

<script setup>
// IMPORTS
import AdminNavigation from "@/Shared/Navigation/AdminNavigation.vue";
import BaseNavigation from "@/Shared/Navigation/BaseNavigation.vue";
import RegularNavigation from "@/Shared/Navigation/RegularNavigation.vue";
import SplashNavigation from "@/Shared/Navigation/SplashNavigation.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { Head } from "@inertiajs/vue3";
import { computed, onBeforeMount, onUnmounted } from "vue";

// PROPS
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    pageType: {
        type: String,
        default: "regular",
    },
});

// STORES
const appStore = useAppStore();
const authStore = useAuthStore();

// VARIABLES
const appName = appStore.appName;

// COMPUTED
const isSplashPage = computed(() => {
    return props.pageType === "splash";
});

const isRegularPage = computed(() => {
    return props.pageType === "regular";
});

const isAdminPage = computed(() => {
    return props.pageType === "admin";
});

onBeforeMount(() => {
    authStore.check();
});

onUnmounted(() => {
    authStore.unsubscribe();
});
</script>
<style>
#header {
    z-index: 10;
    margin-top: -15px;
    position: fixed;
    top: 0;
    left: 0;
}
</style>
