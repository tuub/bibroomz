<template>
    <a
        :href="institutionHomeUri"
        class="flex gap-4 items-center"
        target="_blank"
    >
        <img :src="institutionLogoUri" class="h-8" :alt="institutionTitle" />
        <span
            class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
        >
            {{ appName }}
        </span>
        <span>{{ institutionTitle }}</span>
    </a>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { storeToRefs } from "pinia";

const appStore = useAppStore();
let { appName, institutionHomeUri, institutionLogoUri, institutionTitle } =
    storeToRefs(appStore);
</script>
