import { defineStore } from "pinia";
import { getActiveLanguage } from "laravel-vue-i18n";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAppStore = defineStore({
    id: "app",
    persist: true,
    state: () => {
        return {
            app_name: import.meta.env.VITE_APP_NAME,
            institution: null,
            is_multi_tenancy: false,
            locale: getActiveLanguage(),
        };
    },
    actions: {
        setCurrentInstitution(institution, is_multi_tenancy) {
            this.institution = institution;
            this.is_multi_tenancy = is_multi_tenancy;
        },
        setCurrentLocale(locale) {
            this.locale = locale;
            axios.post(`${baseUrl}/switch-lang`, {
                locale,
            });
        },
    },
    getters: {
        appName: (state) => state.app_name,
        currentInstitution: (state) => state.institution,
        institutionTitle: (state) => state.institution?.title,
        institutionShortTitle: (state) => state.institution?.short_title,
        institutionSlug: (state) => state.institution?.slug,
        institutionHomeUri: (state) => state.institution?.home_uri,
        institutionLogoUri: (state) => state.institution?.logo_uri,
        institutionTeaserUri: (state) => state.institution?.teaser_uri,
        institutionSettings: (state) => state.institution?.settings,
        isMultiTenancy: (state) => state.is_multi_tenancy,
    },
});
