<template>
    <button v-for="(name, code, index) in locales" :key="code" :title="name" @click="switchLocale(code)">
        <span v-if="index > 0" class="px-2">/</span>
        <span :class="activeLocale === code ? 'font-bold' : ''">{{ name }}</span>
    </button>
</template>

<script setup>
import { loadLanguageAsync } from "laravel-vue-i18n";
import { useAppStore } from "@/Stores/AppStore";
import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

const appStore = useAppStore();

const locales = {
    de: "DE",
    en: "EN",
};

const { locale: activeLocale } = storeToRefs(appStore);

const switchLocale = (code) => {
    loadLanguageAsync(code);
    appStore.setCurrentLocale(code);
    activeLocale.value = code;
};

onBeforeMount(() => {
    switchLocale(activeLocale.value);
});
</script>
