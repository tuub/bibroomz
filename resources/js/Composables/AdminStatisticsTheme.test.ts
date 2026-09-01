import { useChartThemeColors } from "@/Composables/AdminStatisticsTheme";
import { useThemeStore } from "@/Stores/ThemeStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent, nextTick } from "vue";

let mediaListeners: EventListener[] = [];
let removeListenerSpy: ReturnType<typeof vi.fn>;

function installMatchMedia() {
    removeListenerSpy = vi.fn();

    Object.defineProperty(window, "matchMedia", {
        configurable: true,
        writable: true,
        value: vi.fn().mockImplementation((query: string) => ({
            media: query,
            matches: false,
            onchange: null,
            addEventListener: vi.fn((event: string, listener: EventListener) => {
                if (event === "change") {
                    mediaListeners.push(listener);
                }
            }),
            removeEventListener: removeListenerSpy,
            dispatchEvent: vi.fn(),
        })),
    });
}

function emitSystemThemeChange() {
    for (const listener of mediaListeners) {
        listener(new Event("change"));
    }
}

function setThemeVariables(muted: string, surface: string, border: string) {
    document.head.innerHTML = `
        <style>
            :root {
                --color-app-muted: ${muted};
                --color-app-surface: ${surface};
                --color-app-border: ${border};
            }
        </style>
    `;
}

const TestComponent = defineComponent({
    setup() {
        return useChartThemeColors();
    },
    template: "<div />",
});

beforeEach(() => {
    setActivePinia(createPinia());
    mediaListeners = [];
    document.head.innerHTML = "";
    installMatchMedia();
});

describe("useChartThemeColors", () => {
    test("reads the theme colors from the document's CSS custom properties", () => {
        setThemeVariables("107 114 128", "255 255 255", "209 213 219");

        const wrapper = mount(TestComponent);

        expect(wrapper.vm.textColor).toBe("rgb(107, 114, 128)");
        expect(wrapper.vm.surfaceColor).toBe("rgb(255, 255, 255)");
        expect(wrapper.vm.borderColor).toBe("rgb(209, 213, 219)");
    });

    test("falls back to the default colors when a CSS variable is not defined", () => {
        const wrapper = mount(TestComponent);

        expect(wrapper.vm.textColor).toBe("#6b7280");
        expect(wrapper.vm.surfaceColor).toBe("#ffffff");
        expect(wrapper.vm.borderColor).toBe("#d1d5db");
    });

    test("recomputes the colors when the theme preference changes", async () => {
        setThemeVariables("107 114 128", "255 255 255", "209 213 219");

        const wrapper = mount(TestComponent);
        const themeStore = useThemeStore();

        expect(wrapper.vm.textColor).toBe("rgb(107, 114, 128)");

        setThemeVariables("245 245 245", "23 23 23", "55 65 81");
        themeStore.setPreference("dark");
        await nextTick();

        expect(wrapper.vm.textColor).toBe("rgb(245, 245, 245)");
        expect(wrapper.vm.surfaceColor).toBe("rgb(23, 23, 23)");
    });

    test("recomputes the colors when the system color scheme changes", async () => {
        setThemeVariables("107 114 128", "255 255 255", "209 213 219");

        const wrapper = mount(TestComponent);

        setThemeVariables("245 245 245", "23 23 23", "55 65 81");
        emitSystemThemeChange();
        await nextTick();

        expect(wrapper.vm.textColor).toBe("rgb(245, 245, 245)");
    });

    test("stops listening for system color scheme changes once unmounted", () => {
        const wrapper = mount(TestComponent);

        wrapper.unmount();

        expect(removeListenerSpy).toHaveBeenCalledWith("change", expect.any(Function));
    });
});
