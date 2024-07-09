import { useAppStore } from "@/Stores/AppStore";

import { defineStore } from "pinia";
import { useToast } from "primevue/usetoast";

export const useToastStore = defineStore({
    id: "toast",

    state: () => ({
        quotaToastMessages: [],
    }),

    actions: {
        initToast() {
            this.toast = useToast();
        },

        addAuthToast({ summary, severity = "success" }) {
            this.toast.add({ life: 3000, severity, summary });
        },

        addHappeningToast({ happening, summary, severity = "success" }) {
            const appStore = useAppStore();

            const happeningStart = appStore.getDateTimeFromString(happening.start);
            const happeningEnd = appStore.getDateTimeFromString(happening.end);

            const date = appStore.formatDate(happeningStart);
            const start = appStore.formatTime(happeningStart);
            const end = appStore.formatTime(happeningEnd);

            const detail = `${date}, ${start} - ${end}`;

            this.toast.add({ life: 5000, severity, summary, detail });
        },

        addQuotaToast({ summary, severity = "warn" }) {
            if (this.quotaToastMessages.includes(summary)) {
                return;
            }

            this.toast.add({ life: 5000, severity, summary });
            this.quotaToastMessages.push(summary);
        },

        removeQuotaToastMessage({ message }) {
            this.quotaToastMessages = this.quotaToastMessages.filter(
                (quotaToastMessage) => quotaToastMessage != message.summary,
            );
        },
    },
});
