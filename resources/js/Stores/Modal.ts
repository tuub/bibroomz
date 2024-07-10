import { defineStore } from "pinia";
import { markRaw } from "vue";

export type Modal = {
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
        view: null,
        content: null,
        payload: null,
        actions: null,
        isOpen: false,
    }),

    actions: {
        open(view: object, content: object, payload: object, actions?: ModalAction[]): void {
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions;

            this.isOpen = true;
        },

        cleanup(): void {
            this.view = null;
            this.content = null;
            this.payload = null;
            this.actions = null;
        },

        close(): void {
            this.isOpen = false;
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
