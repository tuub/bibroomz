import type { ModalInterface } from "flowbite";
import { defineStore } from "pinia";
import { markRaw } from "vue";

export type Modal = {
    modal: ModalInterface | null;
    view: object;
    content: object;
    payload: object;
    actions?: ModalAction[];
    error?: object;
    isOpen: boolean;
};

export type ModalAction = {
    label: string;
    callback: (props?: unknown) => Promise<unknown>;
};

export const useModal = defineStore("modal", {
    state: (): Modal => ({
        modal: null,
        view: null,
        content: null,
        payload: null,
        actions: null,
        error: null,
        isOpen: false,
    }),

    actions: {
        init(modal: ModalInterface) {
            this.modal = modal;
        },

        open(view: object, content: object, payload: object, actions?: ModalAction[]) {
            // using markRaw to avoid over performance as reactive is not required
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions;

            this.isOpen = true;

            this.modal.show();
        },

        cleanup() {
            this.isOpen = false;

            this.view = null;
            this.content = null;
            this.payload = null;
            this.actions = null;
            this.error = null;
        },

        close() {
            this.modal.hide();
        },

        async do(action: ModalAction) {
            this.error = null;
            try {
                await action.callback(this.payload);
                this.close();
            } catch (error) {
                this.error = error.response?.data?.message ?? error;
            }
        },
    },
});

export default useModal;
