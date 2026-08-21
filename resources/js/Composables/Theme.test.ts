import { useTheme } from "@/Composables/Theme";
import { useThemeStore } from "@/Stores/ThemeStore";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

let systemPrefersDark = false;
let mediaListeners: EventListener[] = [];

function installMatchMedia() {
    Object.defineProperty(window, "matchMedia", {
        configurable: true,
        writable: true,
        value: vi.fn().mockImplementation((query: string) => ({
            media: query,
            get matches() {
                return systemPrefersDark;
            },
            onchange: null,
            addEventListener: vi.fn((event: string, listener: EventListener) => {
                if (event === "change") {
                    mediaListeners.push(listener);
                }
            }),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
            addListener: vi.fn(),
            removeListener: vi.fn(),
        })),
    });
}

function emitSystemThemeChange() {
    for (const listener of mediaListeners) {
        listener(new Event("change"));
    }
}

beforeEach(() => {
    setActivePinia(createPinia());
    systemPrefersDark = false;
    mediaListeners = [];
    document.documentElement.className = "";
    document.documentElement.removeAttribute("style");
    document.head.innerHTML = `
        <meta name="theme-color" content="">
        <style>
            :root {
                --color-app-page: 243 244 246;
            }

            .dark {
                --color-app-page: 23 23 23;
            }
        </style>
    `;
    installMatchMedia();
});

describe("useTheme", () => {
    test("applies saved light and dark preferences to the document shell", async () => {
        const theme = useTheme();
        const themeStore = useThemeStore();

        theme.init();

        expect(document.documentElement.classList.contains("dark")).toBe(false);
        expect(document.documentElement.style.colorScheme).toBe("light");
        expect(document.querySelector('meta[name="theme-color"]')?.getAttribute("content")).toBe("rgb(243, 244, 246)");

        themeStore.setPreference("dark");
        await nextTick();

        expect(document.documentElement.classList.contains("dark")).toBe(true);
        expect(document.documentElement.style.colorScheme).toBe("dark");
        expect(document.querySelector('meta[name="theme-color"]')?.getAttribute("content")).toBe("rgb(23, 23, 23)");

        themeStore.setPreference("light");
        await nextTick();

        expect(document.documentElement.classList.contains("dark")).toBe(false);
        expect(document.documentElement.style.colorScheme).toBe("light");
        expect(document.querySelector('meta[name="theme-color"]')?.getAttribute("content")).toBe("rgb(243, 244, 246)");
    });

    test("keeps the system preference wired to prefers-color-scheme changes", async () => {
        const theme = useTheme();
        const themeStore = useThemeStore();

        theme.init();
        themeStore.setPreference("system");
        await nextTick();

        systemPrefersDark = true;
        emitSystemThemeChange();

        expect(document.documentElement.classList.contains("dark")).toBe(true);
        expect(document.documentElement.style.colorScheme).toBe("dark");

        themeStore.setPreference("light");
        await nextTick();
        systemPrefersDark = false;
        emitSystemThemeChange();

        expect(document.documentElement.classList.contains("dark")).toBe(false);
        expect(document.documentElement.style.colorScheme).toBe("light");
    });
});
