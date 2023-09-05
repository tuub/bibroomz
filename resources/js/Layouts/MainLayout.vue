<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description"
              :content="appName"
              head-key="description">
    </Head>
    <section id="content" class="">
        <slot />
    </section>
</template>

<script setup>
import Navigation from "@/Shared/Navigation/RegularNavigation.vue";
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
<style>
    #content{
        margin: 10em 2em 0 2em;
    }
</style>