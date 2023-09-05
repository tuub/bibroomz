<template>
    <Head :title="title ? `${title} - ${appName}` : `${appName}`">
        <slot />
    </Head>
    <section id="header" class=" w-full">
        <SplashNavigation v-if="isSplashPage"></SplashNavigation>
        <RegularNavigation v-if="isRegularPage"></RegularNavigation>
        <AdminNavigation v-if="isAdminPage"></AdminNavigation>
    </section>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import { Head } from '@inertiajs/vue3'
import { computed } from "vue";
import RegularNavigation from "@/Shared/Navigation/RegularNavigation.vue";
import AdminNavigation from "@/Shared/Navigation/AdminNavigation.vue";
import SplashNavigation from "@/Shared/Navigation/SplashNavigation.vue";

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
</script>
<style>
#header{
    z-index: 10;
    margin-top: -15px;
    position: fixed;
    top: 0px;
    left: 0px;
}
</style>
