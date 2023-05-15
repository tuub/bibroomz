import { markRaw } from "vue";
import { defineStore } from "pinia";
import type { ModalInterface } from 'flowbite'

export type Modal = {
    modal: ModalInterface | null;
    view: object;
    content: object;
    payload: object;
    actions?: ModalAction[];
    error?: object;
};

export type ModalAction = {
    label: string;
    callback: (props?: any) => Promise<any>;
};

export const useModal = defineStore("modal", {
    state: (): Modal => ({
        modal: null,
        view: null,
        content: null,
        payload: null,
        actions: null,
        error: null,
    }),
    actions: {
        init(modal: ModalInterface) {
            this.modal = modal;
        },
        open(
            view: Object,
            content: Object,
            payload: Object,
            actions?: ModalAction[]
        ) {
            // using markRaw to avoid over performance as reactive is not required
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions;

            this.modal.show();
        },
        cleanup() {
            this.view = null;
            this.content = null;
            this.payload = null;
            this.actions = null;
            this.error = null;
        },
        close() {
            this.modal.hide();
        }
    },
});

export default useModal;
