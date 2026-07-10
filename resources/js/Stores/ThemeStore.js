import { defineStore } from "pinia";

export const useThemeStore = defineStore({
    id: "theme",
    persist: true,

    state: () => {
        return {
            preference: "system",
        };
    },

    actions: {
        setPreference(preference) {
            this.preference = preference;
        },
    },
});
