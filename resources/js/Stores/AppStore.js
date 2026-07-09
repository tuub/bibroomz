import { withBaseUrl } from "@/baseUrl";

import dayjs from "dayjs";
import "dayjs/locale/de";
import customParseFormat from "dayjs/plugin/customParseFormat";
import { I18n, getActiveLanguage, loadLanguageAsync } from "laravel-vue-i18n";
import { defineStore } from "pinia";

dayjs.extend(customParseFormat);

export const useAppStore = defineStore({
    id: "app",
    persist: true,

    state: () => {
        return {
            appName: import.meta.env.VITE_APP_NAME ?? "BibRoomz",
            resourceGroup: null,
            settings: null,
            hiddenDays: null,
            isMultiTenancy: false,
            systemNotifications: [],
            locale: getActiveLanguage(),
            shortDateFormat: null,
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
            this.systemNotifications = [];

            const message = this.getNotificationMessageFromMappedSettings(settings);

            if (!message) {
                return;
            }

            this.systemNotifications = [
                {
                    title: this.translate(resourceGroup?.institution?.title),
                    message,
                },
            ];
        },

        setStartPageContext(appName, institutions) {
            this.appName = appName;
            this.resourceGroup = null;
            this.settings = null;
            this.hiddenDays = null;
            this.isMultiTenancy = false;
            this.systemNotifications = institutions.flatMap((institution) => {
                const message = this.getNotificationMessageFromRelationSettings(institution?.settings);

                if (!message) {
                    return [];
                }

                return [
                    {
                        title: this.translate(institution?.title),
                        message,
                    },
                ];
            });
        },

        setCurrentLocale(locale) {
            axios
                .post(withBaseUrl("/switch-lang"), {
                    locale,
                })
                .then(() => {
                    const i18n = I18n.getSharedInstance();

                    i18n.setOptions({ fallbackLang: locale === "de" ? "en" : "de" });
                    i18n.loadFallbackLanguage();

                    loadLanguageAsync(locale);
                    this.locale = locale;
                    this.setTemporalFormats(locale);
                });
        },

        setTemporalFormats(locale) {
            switch (locale) {
                case "en":
                    this.dateFormat = "YYYY/MM/DD";
                    this.timeFormat = "HH:mm";
                    this.dateTimeFormat = "YYYY-MM-DDTHH:mm:ss";
                    break;
                default:
                    this.dateFormat = "DD.MM.YYYY";
                    this.timeFormat = "HH:mm";
                    this.dateTimeFormat = "YYYY-MM-DDTHH:mm:ss";
            }
        },

        formatDate(dateTimeStr, isUTC = false) {
            let date = this.getDateTimeFromString(dateTimeStr, isUTC);
            return date.format(this.dateFormat);
        },

        formatFancyDate(dateTimeStr, isUTC = false) {
            let date = this.getDateTimeFromString(dateTimeStr, isUTC);

            let months = {
                de: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
                en: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            };

            return {
                month: months[this.locale][date.month()],
                day: date.date(),
            };
        },

        formatTime(dateTimeStr, isUTC = false, dateTimeStrFormat = null) {
            let time = this.getDateTimeFromString(dateTimeStr, isUTC, dateTimeStrFormat || null);
            return time.format(this.timeFormat);
        },

        formatDateTime(datetimeStr, isUTC = false) {
            let dateTime = this.getDateTimeFromString(datetimeStr, isUTC);
            return dateTime.format(this.dateTimeFormat);
        },

        getDateTimeFromString(datetimeStr, isUTC = false, dateTimeStrFormat) {
            if (isUTC) {
                return dayjs.utc(datetimeStr, dateTimeStrFormat);
            }
            return dayjs(datetimeStr, dateTimeStrFormat);
        },

        translate(translatable, locale) {
            if (!translatable) {
                return;
            }

            locale = locale ?? this.locale;

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

        getNotificationMessageFromMappedSettings(settings) {
            return this.normalizeSystemNotificationMessage(settings?.institution?.system_notification);
        },

        getNotificationMessageFromRelationSettings(settings) {
            if (!Array.isArray(settings)) {
                return "";
            }

            const notificationSetting = settings.find((setting) => setting?.key === "system_notification");

            return this.normalizeSystemNotificationMessage(notificationSetting?.value);
        },

        normalizeSystemNotificationMessage(message) {
            if (typeof message !== "string") {
                return "";
            }

            return message.trim();
        },
    },

    getters: {
        institution: (state) => state.resourceGroup?.institution,
    },
});
