<template>
    <button v-for="(name, code, index) in locales"
            @click="switchLocale(code)"
            :title="name">
        <span v-if="index > 0" class="px-2">/</span>
        <span :class="activeLocale === code ? 'font-bold' : ''">{{ name }}</span>
    </button>

</template>

<script setup>
import {getActiveLanguage, loadLanguageAsync} from 'laravel-vue-i18n';
import {useAppStore} from "@/Stores/AppStore";
import {ref} from "vue";

const appStore = useAppStore()

let locales = {
    'de': 'DE',
    'en': 'EN',
}
let currentLocale = ref(getActiveLanguage());
let activeLocale = ref(currentLocale);

const switchLocale = (code) => {
    loadLanguageAsync(code)
    appStore.setCurrentLocale(code)
    activeLocale.value = code
}
</script>
