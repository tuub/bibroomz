import { getActiveLanguage, loadLanguageAsync } from "laravel-vue-i18n";
import { defineStore } from "pinia";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAppStore = defineStore({
    id: "app",
    persist: true,
    state: () => {
        return {
            appName: import.meta.env.VITE_APP_NAME,
            institution: null,
            isMultiTenancy: false,
            locale: getActiveLanguage(),
        };
    },
    actions: {
        setCurrentInstitution(institution, isMultiTenancy) {
            this.institution = institution;
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
    },
    getters: {
        institutionTitle: (state) => state.translate(state.institution?.title),
        institutionShortTitle: (state) => state.institution?.short_title,
        institutionSlug: (state) => state.institution?.slug,
        institutionHomeUri: (state) => state.institution?.home_uri,
        institutionLogoUri: (state) => state.institution?.logo_uri,
        institutionTeaserUri: (state) => state.institution?.teaser_uri,
        institutionSettings: (state) => state.institution?.settings,
    },
});
