import { useThemeStore } from "@/Stores/ThemeStore";

import { storeToRefs } from "pinia";
import { watch } from "vue";

const PREFERENCE_ORDER = ["system", "light", "dark"];
const media = window.matchMedia("(prefers-color-scheme: dark)");

function resolveIsDark(preference) {
    if (preference === "dark") return true;
    if (preference === "light") return false;
    return media.matches;
}

function applyClass(preference) {
    document.documentElement.classList.toggle("dark", resolveIsDark(preference));
}

export function useTheme() {
    const themeStore = useThemeStore();
    const { preference } = storeToRefs(themeStore);

    const init = () => {
        watch(preference, (value) => applyClass(value), { immediate: true });

        media.addEventListener("change", () => {
            if (preference.value === "system") {
                applyClass(preference.value);
            }
        });
    };

    const cyclePreference = () => {
        const currentIndex = PREFERENCE_ORDER.indexOf(preference.value);
        const nextPreference = PREFERENCE_ORDER[(currentIndex + 1) % PREFERENCE_ORDER.length];
        themeStore.setPreference(nextPreference);
    };

    return {
        preference,
        init,
        cyclePreference,
    };
}
