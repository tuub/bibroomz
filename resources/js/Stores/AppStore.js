import { getActiveLanguage, loadLanguageAsync } from "laravel-vue-i18n";
import { defineStore } from "pinia";
import dayjs from "dayjs";

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
            dateFormat: null,
            timeFormat: null,
            dateTimeFormat: null,
            supportedLocales: ["de", "en"],
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
                    this.setTemporalFormats(locale);
                });
        },
        setTemporalFormats(locale) {
            switch(locale) {
                case 'en':
                    this.dateFormat = 'YYYY/MM/DD';
                    this.timeFormat = 'HH:mm';
                    this.dateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';
                    break;
                default:
                    this.dateFormat = 'DD.MM.YYYY';
                    this.timeFormat = 'HH:mm';
                    this.dateTimeFormat = 'YYYY-MM-DDTHH:mm:ss';
            }
        },
        formatDate(dateTimeStr, isUTC=false) {
            let date = this.getDateTimeFromString(dateTimeStr, isUTC);
            return date.format(this.dateFormat);
        },
        formatTime(dateTimeStr, isUTC=false, dateTimeStrFormat=null) {
            let time = this.getDateTimeFromString(dateTimeStr, isUTC, dateTimeStrFormat || null);
            return time.format(this.timeFormat);
        },
        formatDateTime(datetimeStr, isUTC=false) {
            let dateTime = this.getDateTimeFromString(datetimeStr, isUTC);
            return dateTime.format(this.dateTimeFormat);
        },
        getDateTimeFromString(datetimeStr, isUTC=false, dateTimeStrFormat) {
            if (isUTC) {
                return dayjs.utc(datetimeStr, dateTimeStrFormat)
            }
            return dayjs(datetimeStr, dateTimeStrFormat)
        },
        translate(translatable, locale) {
            if (!translatable) {
                return;
            }

            if (!locale) {
                const appStore = useAppStore();
                locale = appStore.locale;
            }

            if (translatable[locale]) {
                return translatable[locale];
            }

            for (const supportedLocale of this.supportedLocales) {
                if (translatable[supportedLocale]) {
                    return translatable[supportedLocale];
                }
            }

            return "";
        },
    },
    getters: {
        institution: (state) => state.resourceGroup?.institution,
    },
});
