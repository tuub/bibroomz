import { useAppStore } from "@/Stores/AppStore";

import dayjs from "dayjs";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

const i18nMock = {
    setOptions: vi.fn(),
    loadFallbackLanguage: vi.fn(),
};

const loadLanguageAsyncMock = vi.fn();

vi.mock("laravel-vue-i18n", () => ({
    getActiveLanguage: () => "de",
    loadLanguageAsync: (locale: string) => loadLanguageAsyncMock(locale),
    I18n: { getSharedInstance: () => i18nMock },
}));

const axiosMock = { post: vi.fn() };

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    axiosMock.post.mockResolvedValue({ data: {} });
    globalThis.axios = axiosMock as unknown as typeof axios;
});

describe("date/time formatting", () => {
    test("formatDate uses the locale's date format", () => {
        const store = useAppStore();
        store.setTemporalFormats("de");
        expect(store.formatDate("2026-03-05T10:00:00")).toBe("05.03.2026");
    });

    test("formatDate accepts a Dayjs instance directly", () => {
        const store = useAppStore();
        store.setTemporalFormats("de");
        expect(store.formatDate(dayjs("2026-03-05T10:00:00"))).toBe("05.03.2026");
    });

    test("formatTime formats using the time format", () => {
        const store = useAppStore();
        store.setTemporalFormats("de");
        expect(store.formatTime("2026-03-05T14:30:00")).toBe("14:30");
    });

    test("formatTime parses using a custom parse format", () => {
        const store = useAppStore();
        store.setTemporalFormats("de");
        expect(store.formatTime("14-30", false, "HH-mm")).toBe("14:30");
    });

    test("formatDateTime formats using the datetime format", () => {
        const store = useAppStore();
        store.setTemporalFormats("en");
        expect(store.formatDateTime("2026-03-05T14:30:00")).toBe("2026-03-05T14:30:00");
    });

    test("isUTC formats using dayjs.utc instead of local time", () => {
        const store = useAppStore();
        store.setTemporalFormats("de");
        expect(store.formatTime("2026-03-05T14:30:00Z", true)).toBe("14:30");
    });

    test("formatFancyDate returns the localized month name and day", () => {
        const store = useAppStore();
        store.locale = "de";
        expect(store.formatFancyDate("2026-03-05T10:00:00")).toEqual({ month: "Mär", day: 5 });
    });
});

describe("setTemporalFormats", () => {
    test("uses English date/time formats for en", () => {
        const store = useAppStore();
        store.setTemporalFormats("en");
        expect(store.dateFormat).toBe("YYYY/MM/DD");
        expect(store.timeFormat).toBe("HH:mm");
        expect(store.dateTimeFormat).toBe("YYYY-MM-DDTHH:mm:ss");
    });

    test("falls back to the default format for any other locale", () => {
        const store = useAppStore();
        store.setTemporalFormats("fr");
        expect(store.dateFormat).toBe("DD.MM.YYYY");
    });
});

describe("translate", () => {
    test("returns an empty string for a falsy translatable", () => {
        const store = useAppStore();
        expect(store.translate(undefined)).toBe("");
    });

    test("returns the value for the current locale", () => {
        const store = useAppStore();
        store.locale = "de";
        expect(store.translate({ de: "Hallo", en: "Hello" })).toBe("Hallo");
    });

    test("passes through a raw string unchanged", () => {
        const store = useAppStore();
        expect(store.translate("Already translated")).toBe("Already translated");
    });

    test("falls back to another supported locale when the current one is missing", () => {
        const store = useAppStore();
        store.locale = "de";
        expect(store.translate({ en: "Hello" })).toBe("Hello");
    });

    test("returns an empty string when no supported locale matches", () => {
        const store = useAppStore();
        store.locale = "de";
        expect(store.translate({ fr: "Bonjour" })).toBe("");
    });

    test("accepts an explicit locale override", () => {
        const store = useAppStore();
        store.locale = "de";
        expect(store.translate({ de: "Hallo", en: "Hello" }, "en")).toBe("Hello");
    });
});

describe("setCurrentLocale", () => {
    test("posts the new locale and updates i18n + temporal formats", async () => {
        const store = useAppStore();

        store.setCurrentLocale("en");
        await Promise.resolve();

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/switch-lang", { locale: "en" });
        expect(i18nMock.setOptions).toHaveBeenCalledWith({ fallbackLang: "de" });
        expect(i18nMock.loadFallbackLanguage).toHaveBeenCalledOnce();
        expect(loadLanguageAsyncMock).toHaveBeenCalledWith("en");
        expect(store.locale).toBe("en");
        expect(store.dateFormat).toBe("YYYY/MM/DD");
    });
});

describe("setCurrent", () => {
    test("stores context and builds notifications from settings + institution title", () => {
        const store = useAppStore();
        store.globalSystemNotification = "Global maintenance";
        store.locale = "de";

        const resourceGroup = { institution: { title: { de: "TU Berlin" } } };
        const settings = { institution: { system_notification: "  Local heads up  " } };

        store.setCurrent(resourceGroup, settings, [0, 6], true);

        expect(store.resourceGroup).toEqual(resourceGroup);
        expect(store.settings).toEqual(settings);
        expect(store.hiddenDays).toEqual([0, 6]);
        expect(store.isMultiTenancy).toBe(true);
        expect(store.systemNotifications).toEqual([
            { message: "Global maintenance" },
            { title: "TU Berlin", message: "Local heads up" },
        ]);
    });

    test("omits the institution notification when there is no message", () => {
        const store = useAppStore();

        store.setCurrent({ institution: { title: { de: "TU Berlin" } } }, { institution: {} }, null, false);

        expect(store.systemNotifications).toEqual([]);
    });
});

describe("setStartPageContext", () => {
    test("resets context back to the start page defaults", () => {
        const store = useAppStore();
        store.setCurrent({ institution: {} }, {}, [0], true);

        store.setStartPageContext("BibRoomz");

        expect(store.appName).toBe("BibRoomz");
        expect(store.resourceGroup).toBeNull();
        expect(store.settings).toBeNull();
        expect(store.hiddenDays).toBeNull();
        expect(store.isMultiTenancy).toBe(false);
        expect(store.systemNotifications).toEqual([]);
    });
});

describe("setGlobalSystemNotification", () => {
    test("trims string messages", () => {
        const store = useAppStore();
        store.setGlobalSystemNotification("  Scheduled downtime  ");
        expect(store.globalSystemNotification).toBe("Scheduled downtime");
    });

    test("normalizes non-string messages to an empty string", () => {
        const store = useAppStore();
        store.setGlobalSystemNotification(null);
        expect(store.globalSystemNotification).toBe("");
    });
});

describe("getters", () => {
    test("institution reads from the current resource group", () => {
        const store = useAppStore();
        store.resourceGroup = { institution: { title: { en: "TU Berlin" } } };
        expect(store.institution).toEqual({ title: { en: "TU Berlin" } });
    });

    test("globalNotifications wraps the global notification message", () => {
        const store = useAppStore();
        store.globalSystemNotification = "Downtime";
        expect(store.globalNotifications).toEqual([{ message: "Downtime" }]);
    });

    test("globalNotifications is empty without a message", () => {
        const store = useAppStore();
        expect(store.globalNotifications).toEqual([]);
    });
});
