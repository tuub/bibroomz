<template>
    <div class="flex">
        <div class="mr-2"><i class="pi pi-language"></i></div>
        <div>
            <template v-for="(name, code, index) in locales" :key="code">
                <template v-if="index > 0"> / </template>
                <Link
                    href="#"
                    :title="name"
                    :class="{ 'i18n-active': activeLocale === code }"
                    @click="switchLocale(code)"
                    >{{ code.toUpperCase() }}
                </Link>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/Stores/AppStore";

import { Link } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

const appStore = useAppStore();

const locales = {
    de: "DE",
    en: "EN",
};

const { locale: activeLocale } = storeToRefs(appStore);

const switchLocale = (code: keyof typeof locales) => {
    appStore.setCurrentLocale(code);
};

onBeforeMount(() => {
    switchLocale(activeLocale.value as keyof typeof locales);
});
</script>

<style lang="postcss" scoped>
@reference "../../../css/main.css";

.i18n-active {
    @apply font-extrabold;
}
</style>
