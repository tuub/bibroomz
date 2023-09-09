<template>
    <Head>
        <title>{{ appName }}</title>
        <meta type="description" :content="appName" head-key="description" />
    </Head>
    <section id="content" class="">
        <slot />
    </section>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const appName = appStore.appName;
</script>
<style>
#content {
    margin: 10em 2em 0 2em;
}
</style>
