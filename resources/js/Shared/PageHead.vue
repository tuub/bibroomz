<template>
    <Head :title="title ? `${title} - ${appName}` : `${appName}`">
        <slot />
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
import { Head } from "@inertiajs/vue3";
import BaseNavigation from "@/Shared/Navigation/BaseNavigation.vue";
import RegularNavigation from "@/Shared/Navigation/RegularNavigation.vue";
import AdminNavigation from "@/Shared/Navigation/AdminNavigation.vue";
import SplashNavigation from "@/Shared/Navigation/SplashNavigation.vue";
import { computed } from "vue";

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

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appName = import.meta.env.VITE_APP_NAME;

const isSplashPage = computed(() => {
    return props.pageType === "splash";
});

const isRegularPage = computed(() => {
    return props.pageType === "regular";
});

const isAdminPage = computed(() => {
    return props.pageType === "admin";
});
</script>
<style>
#header {
    z-index: 10;
    margin-top: -15px;
    position: fixed;
    top: 0px;
    left: 0px;
}
</style>
