<template>
    <button v-for="(name, code, i) in locales"
            @click="switchLocale(code)"
            class="mr-2"
            :title="name">
        <span class="fi fis" :class="[activeLocale === code ? 'outline-2 outline-black' : '', getFlagIcon(code)]" />
    </button>

</template>

<script setup>
import {getActiveLanguage, loadLanguageAsync} from 'laravel-vue-i18n';
import {useAppStore} from "@/Stores/AppStore";
import {ref} from "vue";

const appStore = useAppStore()

let locales = {
    'de': 'DEUTSCH',
    'en': 'ENGLISH',
}
let currentLocale = ref(getActiveLanguage());
let activeLocale = ref(currentLocale);

const switchLocale = (code) => {
    loadLanguageAsync(code)
    appStore.setCurrentLocale(code)
    activeLocale.value = code
}

const getFlagIcon = (code) => {
    let flagCode = '';
    switch(code) {
        case 'de':
            flagCode = 'de'
            break;
        case 'en':
            flagCode = 'gb'
            break;
        default:
            flagCode = 'de'
    }

    return 'fi-' + flagCode
}

</script>
