import { useThemeStore } from "@/Stores/ThemeStore";

import { storeToRefs } from "pinia";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

function readColor(variable: string, fallback: string): string {
    if (typeof window === "undefined") return fallback;

    const value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();

    return value ? `rgb(${value.replace(/\s+/g, ", ")})` : fallback;
}

export function useChartThemeColors() {
    const { preference } = storeToRefs(useThemeStore());
    const themeTick = ref(0);
    const darkModeMedia = typeof window !== "undefined" ? window.matchMedia("(prefers-color-scheme: dark)") : null;

    function bumpThemeTick() {
        themeTick.value += 1;
    }

    watch(preference, bumpThemeTick);
    onMounted(() => darkModeMedia?.addEventListener("change", bumpThemeTick));
    onUnmounted(() => darkModeMedia?.removeEventListener("change", bumpThemeTick));

    const textColor = computed(() => {
        void themeTick.value;
        return readColor("--color-app-muted", "#6b7280");
    });
    const surfaceColor = computed(() => {
        void themeTick.value;
        return readColor("--color-app-surface", "#ffffff");
    });
    const borderColor = computed(() => {
        void themeTick.value;
        return readColor("--color-app-border", "#d1d5db");
    });

    return { textColor, surfaceColor, borderColor };
}
