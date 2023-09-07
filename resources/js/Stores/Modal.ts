import type { ModalInterface } from "flowbite";
import { defineStore } from "pinia";
import { markRaw } from "vue";

export type Modal = {
    modal: ModalInterface | null;
    view: object;
    content: object;
    payload: object;
    actions?: ModalAction[];
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
        isOpen: false,
    }),

    actions: {
        init(modal: ModalInterface): void {
            this.modal = modal;
        },

        open(view: object, content: object, payload: object, actions?: ModalAction[]): void {
            // using markRaw to avoid over performance as reactive is not required
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions;

            this.isOpen = true;

            this.modal.show();
        },

        cleanup(): void {
            this.isOpen = false;

            this.view = null;
            this.content = null;
            this.payload = null;
            this.actions = null;
        },

        close(): void {
            this.modal.hide();
        },

        submit(): void {
            if (this.actions.length != 1) {
                return;
            }

            this.actions[0].callback(this.payload);
        },
    },
});

export default useModal;
