import { getActiveLanguage, loadLanguageAsync } from "laravel-vue-i18n";
import { defineStore } from "pinia";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAppStore = defineStore({
    id: "app",
    persist: true,
    state: () => {
        return {
            appName: import.meta.env.VITE_APP_NAME,
            resourceGroup: null,
            settings: null,
            hiddenDays: null,
            isMultiTenancy: false,
            locale: getActiveLanguage(),
        };
    },
    actions: {
        setCurrent(resourceGroup, settings, hiddenDays, isMultiTenancy) {
            this.resourceGroup = resourceGroup;
            this.settings = settings;
            this.hiddenDays = hiddenDays;
            this.isMultiTenancy = isMultiTenancy;
        },
        setCurrentLocale(locale) {
            axios
                .post(`${baseUrl}/switch-lang`, {
                    locale,
                })
                .then(() => {
                    loadLanguageAsync(locale);
                    this.locale = locale;
                });
        },
        translate(translatable, locale) {
            if (!translatable) {
                return;
            }

            if (!locale) {
                const appStore = useAppStore();
                locale = appStore.locale;
            }

            return translatable[locale] ?? translatable["de"] ?? translatable["en"];
        },
    },
    getters: {
        institution: (state) => state.resourceGroup?.institution,
    },
});
