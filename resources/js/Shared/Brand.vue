<template>
    <div class="brand-wrapper block w-full h-8">
        <a :href="route('start')" class="float-left">
            {{ appName }}
        </a>
        <a :href="institutionHomeUri" class="float-right" target="_blank">
            <img :src="institutionLogoUri" class="float-right h-8" :alt="institutionTitle" />
        </a>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

import { storeToRefs } from "pinia";

const appStore = useAppStore();
const { appName, institutionHomeUri, institutionLogoUri, institutionTitle } = storeToRefs(appStore);
</script>
<style>
.brand-wrapper {
    font-size: x-large;
    font-weight: 800;
}
</style>
