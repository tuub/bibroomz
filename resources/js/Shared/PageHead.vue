<template>
    <Head :title="title ? `${title} - ${appName}` : `${appName}`">
        <slot />
    </Head>
    <section id="header" class="p-6 bg-gray-200">
        <Navigation v-if="isRegularPage"></Navigation>
        <AdminNavigation v-if="isAdminPage"></AdminNavigation>
        <SplashNavigation v-if="isSplashPage"></SplashNavigation>
    </section>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { Head } from '@inertiajs/vue3'
import { computed } from "vue";
import Navigation from "@/Shared/Navigation.vue";
import AdminNavigation from "@/Shared/AdminNavigation.vue";
import SplashNavigation from "@/Shared/SplashNavigation.vue";

const props = defineProps({
    title: String,
    page_type: {
        type: String,
        default: 'regular',
    },
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

let appName = import.meta.env.VITE_APP_NAME

const isSplashPage = computed(() => {
    return props.page_type === 'splash'
})

const isRegularPage = computed(() => {
    return props.page_type === 'regular'
})

const isAdminPage = computed(() => {
    return props.page_type === 'admin'
})

// ------------------------------------------------
// Mount
// ------------------------------------------------

</script>
