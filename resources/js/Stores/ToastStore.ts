import { useAppStore } from "@/Stores/AppStore";
import type { Happening } from "@/Stores/HappeningStore";

import { defineStore } from "pinia";
import type { ToastMessageOptions } from "primevue/toast";
import type { ToastServiceMethods } from "primevue/toastservice";
import { useToast } from "primevue/usetoast";

type ToastMessage = ToastMessageOptions & { id?: string };

function equals(a: ToastMessage, b: ToastMessage) {
    return JSON.stringify(a) === JSON.stringify(b);
}

type ToastStoreState = {
    toastMessages: ToastMessage[];
    toast: ToastServiceMethods | null;
};

export const useToastStore = defineStore({
    id: "toast",

    state: (): ToastStoreState => ({
        toastMessages: [],
        toast: null,
    }),

    actions: {
        initToast() {
            this.toast = useToast();
        },

        addToast(options: ToastMessage) {
            if (this.toastMessages.filter((message: ToastMessage) => equals(message, options)).length > 0) {
                return;
            }

            this.toastMessages.push(options);
            this.toast?.add(options);
        },

        addAuthToast({ summary, severity = "success" }: { summary?: string; severity?: ToastMessage["severity"] }) {
            this.addToast({ id: "auth", life: 3000, severity, summary });
        },

        addHappeningToast({
            happening,
            summary,
            severity = "success",
        }: {
            happening: Happening;
            summary?: string;
            severity?: ToastMessage["severity"];
        }) {
            const appStore = useAppStore();

            const happeningStart = appStore.getDateTimeFromString(happening.start);
            const happeningEnd = appStore.getDateTimeFromString(happening.end);

            const date = appStore.formatDate(happeningStart);
            const start = appStore.formatTime(happeningStart);
            const end = appStore.formatTime(happeningEnd);

            const message: ToastMessage = {
                detail: `${date}, ${start} - ${end}`,
                id: "happening",
                life: 5000,
                severity,
                summary,
            };

            this.addToast(message);
        },

        addQuotaToast({ summary, severity = "warn" }: { summary?: string; severity?: ToastMessage["severity"] }) {
            this.addToast({ id: "quota", life: 5000, severity, summary });
        },

        addUserGroupToast({ summary, severity = "warn" }: { summary?: string; severity?: ToastMessage["severity"] }) {
            this.addToast({ id: "userGroup", life: 3000, severity, summary });
        },

        removeToastMessage(params: { message: ToastMessage }) {
            this.toastMessages = this.toastMessages.filter((message: ToastMessage) => !equals(message, params.message));
        },
    },
});
