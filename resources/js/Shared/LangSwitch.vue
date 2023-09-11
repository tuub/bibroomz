<template>
    <div class="language-button-wrapper">
        <button
            v-for="(name, code, index) in locales"
            :key="code"
            class="language-switch"
            :title="name"
            @click="switchLocale(code)"
        >
            <span :class="{ 'locale-active': activeLocale === code }">{{ code }}</span>
            <span v-if="index > 0" class="px-2">/</span>
        </button>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

import { loadLanguageAsync } from "laravel-vue-i18n";
import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

const appStore = useAppStore();

const locales = {
    en: "EN",
    de: "DE",
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
<style>
.language-switch {
    float: right;
}

.language-button-wrapper {
    display: contents;
}

.locale-active {
    font-weight: bold;
}
</style>
