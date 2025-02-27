import { useAppStore } from "@/Stores/AppStore";

import { defineStore } from "pinia";
import { useToast } from "primevue/usetoast";

function equals(a, b) {
    return JSON.stringify(a) === JSON.stringify(b);
}

export const useToastStore = defineStore({
    id: "toast",

    state: () => ({
        toastMessages: [],
    }),

    actions: {
        initToast() {
            this.toast = useToast();
        },

        addToast(options) {
            if (this.toastMessages.filter((message) => equals(message, options)).length > 0) {
                return;
            }

            this.toastMessages.push(options);
            this.toast.add(options);
        },

        addAuthToast({ summary, severity = "success" }) {
            this.addToast({ id: "auth", life: 3000, severity, summary });
        },

        addHappeningToast({ happening, summary, severity = "success" }) {
            const appStore = useAppStore();

            const happeningStart = appStore.getDateTimeFromString(happening.start);
            const happeningEnd = appStore.getDateTimeFromString(happening.end);

            const date = appStore.formatDate(happeningStart);
            const start = appStore.formatTime(happeningStart);
            const end = appStore.formatTime(happeningEnd);

            const message = {
                detail: `${date}, ${start} - ${end}`,
                id: "happening",
                life: 5000,
                severity,
                summary,
            };

            this.addToast(message);
        },

        addQuotaToast({ summary, severity = "warn" }) {
            this.addToast({ id: "quota", life: 5000, severity, summary });
        },

        addUserGroupToast({ summary, severity = "warn" }) {
            this.addToast({ id: "userGroup", life: 3000, severity, summary });
        },

        removeToastMessage(params) {
            this.toastMessages = this.toastMessages.filter((message) => !equals(message, params.message));
        },
    },
});
