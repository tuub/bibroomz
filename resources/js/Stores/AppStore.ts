import type { Translatable } from "@/Types/Admin";
import { withBaseUrl } from "@/baseUrl";

import dayjs, { type Dayjs } from "dayjs";
import "dayjs/locale/de";
import customParseFormat from "dayjs/plugin/customParseFormat";
import utc from "dayjs/plugin/utc";
import { I18n, getActiveLanguage, loadLanguageAsync } from "laravel-vue-i18n";
import { defineStore } from "pinia";

dayjs.extend(customParseFormat);
dayjs.extend(utc);

export type TranslatableValue = string | Translatable;

export interface ResourceGroupSetting {
    key?: string;
    value?: string | number | null;
}

export interface Institution {
    id?: number | string;
    title?: Translatable;
    description?: Translatable;
    location?: string;
    slug?: string;
    home_uri?: string;
    logo_uri?: string;
    teaser_uri?: string;
    resource_groups?: ResourceGroup[];
    system_notification?: string | null;
    [key: string]: unknown;
}

export interface ResourceGroup {
    id?: number | string;
    institution_id?: number | string;
    slug?: string;
    institution?: Institution;
    term_singular?: Translatable;
    title?: Translatable;
    description?: Translatable;
    settings?: ResourceGroupSetting[];
    [key: string]: unknown;
}

export interface ResourceGroupSettings {
    quota_happening_block_hours?: number | string;
    quota_weekly_happenings?: number | string;
    quota_weekly_hours?: number | string;
    quota_daily_hours?: number | string;
    weeks_in_advance?: number | string;
    time_slot_length?: string;
    start_time_slot?: string;
    end_time_slot?: string;
    is_label_enabled?: number | string;
    [key: string]: unknown;
}

export interface Settings {
    institution?: {
        system_notification?: string | null;
    };
    resource_group?: ResourceGroupSettings;
    [key: string]: unknown;
}

export type SystemNotification = {
    title?: string;
    message: string;
};

type AppStoreState = {
    appName: string;
    resourceGroup: ResourceGroup | null;
    settings: Settings | null;
    hiddenDays: number[] | null;
    isMultiTenancy: boolean;
    systemNotifications: SystemNotification[];
    globalSystemNotification: string | null;
    locale: string;
    shortDateFormat: string | null;
    dateFormat: string | null;
    timeFormat: string | null;
    dateTimeFormat: string | null;
    supportedLocales: string[];
};

export const useAppStore = defineStore({
    id: "app",
    persist: true,

    state: (): AppStoreState => {
        return {
            appName: import.meta.env.VITE_APP_NAME ?? "BibRoomz",
            resourceGroup: null,
            settings: null,
            hiddenDays: null,
            isMultiTenancy: false,
            systemNotifications: [],
            globalSystemNotification: null,
            locale: getActiveLanguage(),
            shortDateFormat: null,
            dateFormat: null,
            timeFormat: null,
            dateTimeFormat: null,
            supportedLocales: ["de", "en"],
        };
    },

    actions: {
        setCurrent(
            resourceGroup: ResourceGroup | null,
            settings: Settings | null,
            hiddenDays: number[] | null,
            isMultiTenancy: boolean,
        ) {
            this.resourceGroup = resourceGroup;
            this.settings = settings;
            this.hiddenDays = hiddenDays;
            this.isMultiTenancy = isMultiTenancy;

            const message = this.getNotificationMessageFromMappedSettings(settings);
            const institutionNotifications: SystemNotification[] = message
                ? [
                      {
                          title: this.translate(resourceGroup?.institution?.title),
                          message,
                      },
                  ]
                : [];

            this.systemNotifications = [...this.globalNotifications, ...institutionNotifications];
        },

        setStartPageContext(appName: string) {
            this.appName = appName;
            this.resourceGroup = null;
            this.settings = null;
            this.hiddenDays = null;
            this.isMultiTenancy = false;
            this.systemNotifications = [];
        },

        setGlobalSystemNotification(message: unknown) {
            this.globalSystemNotification = this.normalizeSystemNotificationMessage(message);
        },

        setCurrentLocale(locale: string) {
            void axios
                .post(withBaseUrl("/switch-lang"), {
                    locale,
                })
                .then(() => {
                    const i18n = I18n.getSharedInstance();

                    i18n.setOptions({ fallbackLang: locale === "de" ? "en" : "de" });
                    i18n.loadFallbackLanguage();

                    void loadLanguageAsync(locale);
                    this.locale = locale;
                    this.setTemporalFormats(locale);
                });
        },

        setTemporalFormats(locale: string) {
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

        formatDate(dateTimeStr: dayjs.ConfigType, isUTC = false) {
            const date = this.getDateTimeFromString(dateTimeStr, isUTC);
            return date.format(this.dateFormat ?? undefined);
        },

        formatFancyDate(dateTimeStr: dayjs.ConfigType, isUTC = false) {
            const date = this.getDateTimeFromString(dateTimeStr, isUTC);

            const months: Record<string, string[]> = {
                de: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
                en: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            };

            return {
                month: months[this.locale]?.[date.month()] ?? "",
                day: date.date(),
            };
        },

        formatTime(dateTimeStr: dayjs.ConfigType, isUTC = false, dateTimeStrFormat: string | null = null) {
            const time = this.getDateTimeFromString(dateTimeStr, isUTC, dateTimeStrFormat || undefined);
            return time.format(this.timeFormat ?? undefined);
        },

        formatDateTime(datetimeStr: dayjs.ConfigType, isUTC = false) {
            const dateTime = this.getDateTimeFromString(datetimeStr, isUTC);
            return dateTime.format(this.dateTimeFormat ?? undefined);
        },

        getDateTimeFromString(datetimeStr: dayjs.ConfigType, isUTC = false, dateTimeStrFormat?: string): Dayjs {
            if (isUTC) {
                return dayjs.utc(datetimeStr, dateTimeStrFormat);
            }
            return dayjs(datetimeStr, dateTimeStrFormat);
        },

        translate(translatable: TranslatableValue | undefined, locale?: string): string {
            if (typeof translatable === "string") {
                return translatable;
            }

            if (!translatable) {
                return "";
            }

            locale = locale ?? this.locale;

            const currentTranslation = translatable[locale];
            if (currentTranslation) {
                return currentTranslation;
            }

            for (const supportedLocale of this.supportedLocales) {
                const fallbackTranslation = translatable[supportedLocale];
                if (fallbackTranslation) {
                    return fallbackTranslation;
                }
            }

            return "";
        },

        getNotificationMessageFromMappedSettings(settings: Settings | null) {
            return this.normalizeSystemNotificationMessage(settings?.institution?.system_notification);
        },

        normalizeSystemNotificationMessage(message: unknown): string {
            if (typeof message !== "string") {
                return "";
            }

            return message.trim();
        },
    },

    getters: {
        institution: (state) => state.resourceGroup?.institution,
        globalNotifications: (state): SystemNotification[] =>
            state.globalSystemNotification ? [{ message: state.globalSystemNotification }] : [],
    },
});
