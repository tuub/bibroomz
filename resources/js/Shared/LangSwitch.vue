<template>
    <div class="language-button-wrapper">
        <button
            v-for="(name, code, index) in locales"
            :key="code"
            class="language-switch"
            :title="name"
            @click="switchLocale(code)"
        >
            <span :class="{ 'locale-active': activeLocale === code }">{{ code.toUpperCase() }}</span>
            <span v-if="index > 0" class="px-2">/</span>
        </button>
    </div>
</template>

<script setup>
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
    appStore.setCurrentLocale(code);
};

onBeforeMount(() => {
    switchLocale(activeLocale.value);
});
</script>
<style>
.language-switch {
    float: right;
}

.locale-active {
    font-weight: bold;
}

.language-button-wrapper {
    position: absolute;
    right: 125px;
}

@media only screen and (max-width: 500px) {
    .language-button-wrapper {
        position: absolute;
        top: 44px;
        right: 120px;
        font-size: 11px;
    }
}
</style>
