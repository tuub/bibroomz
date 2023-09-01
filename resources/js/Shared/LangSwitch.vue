<template>
    <div class="language-button-wrapper">
        <button class="language-switch" v-for="(name, code, index) in locales" :key="code" :title="name" @click="switchLocale(code)">
            <span class="">{{ name }}</span>
            <span v-if="index > 0" class="px-2">/</span>
            
        </button>
    </div>
</template>

<script setup>
import { loadLanguageAsync } from "laravel-vue-i18n";
import { useAppStore } from "@/Stores/AppStore";
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
.language-switch{
    float: right;
}
.language-button-wrapper{
    display: contents;
}
</style>