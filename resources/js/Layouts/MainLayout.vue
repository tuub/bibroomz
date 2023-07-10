<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description"
              :content="appName"
              head-key="description">
    </Head>
    <section id="header" class="p-6 bg-gray-200">
        <Navigation></Navigation>
    </section>
    <section id="content" class="p-6">
        <slot />
    </section>
</template>

<script setup>
import Navigation from "@/Shared/Navigation.vue";
import { useAppStore } from "@/Stores/AppStore"

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let appName = appStore.appName;
</script>
