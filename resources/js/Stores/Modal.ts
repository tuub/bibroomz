import { defineStore } from "pinia";
import { type Component, markRaw } from "vue";

export type ModalContent = {
    title?: string;
    description?: string;
    message?: string;
};

export type ModalOpenPayload = {
    view: Component;
    content: ModalContent;
    payload: unknown;
    actions?: ModalAction[] | null;
};

export type Modal = {
    view: Component | null;
    content: ModalContent | null;
    payload: unknown;
    actions: ModalAction[] | null;
    isOpen: boolean;
};

export type ModalAction = {
    label: string;
    testId?: string;
    callback: {
        bivarianceHack: (props?: unknown) => Promise<unknown>;
    }["bivarianceHack"];
};

const getBackgroundElements = () => {
    return document.querySelectorAll("header, main, footer");
};

const blurBackgroundElements = () => {
    getBackgroundElements().forEach((element) => {
        element.classList.add("blur-sm");
    });
};

const unblurBackgroundElements = () => {
    getBackgroundElements().forEach((element) => {
        element.classList.remove("blur-sm");
    });
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
        open(view: Component, content: ModalContent, payload: unknown, actions?: ModalAction[] | null): void {
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions;

            this.isOpen = true;
            blurBackgroundElements();
        },

        cleanup(): void {
            this.view = null;
            this.content = null;
            this.payload = null;
            this.actions = null;

            unblurBackgroundElements();
        },

        close(): void {
            this.isOpen = false;
            unblurBackgroundElements();
        },

        submit(): void {
            if (!this.actions || this.actions.length !== 1) {
                return;
            }

            this.actions[0].callback(this.payload);
        },
    },
});

export default useModal;
