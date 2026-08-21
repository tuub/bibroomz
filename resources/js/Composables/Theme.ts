import { type ThemePreference, useThemeStore } from "@/Stores/ThemeStore";

import { storeToRefs } from "pinia";
import { watch } from "vue";

const PREFERENCE_ORDER: ThemePreference[] = ["system", "light", "dark"];
const THEME_COLOR_VARIABLE = "--color-app-page";

type ResolvedTheme = "light" | "dark";

function getDarkModeMedia() {
    return window.matchMedia("(prefers-color-scheme: dark)");
}

function resolveTheme(preference: ThemePreference): ResolvedTheme {
    if (preference === "dark") return "dark";
    if (preference === "light") return "light";
    return getDarkModeMedia().matches ? "dark" : "light";
}

function resolveThemeColor() {
    const appPageColor = getComputedStyle(document.documentElement).getPropertyValue(THEME_COLOR_VARIABLE).trim();

    return appPageColor ? `rgb(${appPageColor.replace(/\s+/g, ", ")})` : null;
}

function applyTheme(preference: ThemePreference) {
    const resolvedTheme = resolveTheme(preference);
    const isDark = resolvedTheme === "dark";
    const themeColor = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');

    document.documentElement.classList.toggle("dark", isDark);
    document.documentElement.style.colorScheme = resolvedTheme;

    const resolvedThemeColor = resolveThemeColor();

    if (themeColor && resolvedThemeColor) {
        themeColor.setAttribute("content", resolvedThemeColor);
    }
}

export function useTheme() {
    const themeStore = useThemeStore();
    const { preference } = storeToRefs(themeStore);
    const media = getDarkModeMedia();

    const init = () => {
        watch(preference, (value) => applyTheme(value), { immediate: true });

        media.addEventListener("change", () => {
            if (preference.value === "system") {
                applyTheme(preference.value);
            }
        });
    };

    const cyclePreference = () => {
        const currentIndex = PREFERENCE_ORDER.indexOf(preference.value);
        const nextPreference = PREFERENCE_ORDER[(currentIndex + 1) % PREFERENCE_ORDER.length] ?? "system";
        themeStore.setPreference(nextPreference);
    };

    return {
        preference,
        init,
        cyclePreference,
    };
}
