import { defineStore } from "pinia";

export type ThemePreference = "system" | "light" | "dark";

type ThemeStoreState = {
    preference: ThemePreference;
};

export const useThemeStore = defineStore("theme", {
    persist: true,

    state: (): ThemeStoreState => {
        return {
            preference: "system",
        };
    },

    actions: {
        setPreference(preference: ThemePreference) {
            this.preference = preference;
        },
    },
});
