import { defineStore } from "pinia";
import { type Component, markRaw } from "vue";

export type ModalContent = {
    title?: string;
    description?: string;
    message?: string;
};

export type ModalOpenPayload<Props = unknown> = {
    view: Component;
    content: ModalContent;
    payload: Props;
    actions?: ModalAction<Props>[] | null;
};

export type Modal = {
    view: Component | null;
    content: ModalContent | null;
    payload: unknown;
    actions: ModalAction[] | null;
    isOpen: boolean;
};

export type ModalAction<Props = unknown> = {
    label: string;
    testId?: string;
    callback: {
        bivarianceHack: (props: Props) => Promise<unknown>;
    }["bivarianceHack"];
};

const getBackgroundElements = () => {
    return document.querySelectorAll("header, main, footer");
};

const blurBackgroundElements = () => {
    getBackgroundElements().forEach((element) => {
        element.classList.add("blur-xs");
    });
};

const unblurBackgroundElements = () => {
    getBackgroundElements().forEach((element) => {
        element.classList.remove("blur-xs");
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
        open<Props>(
            view: Component,
            content: ModalContent,
            payload: Props,
            actions?: ModalAction<Props>[] | null,
        ): void {
            this.view = markRaw(view);

            this.content = content;
            this.payload = payload;
            this.actions = actions as ModalAction[] | null;

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

            const [action] = this.actions;
            if (!action) {
                return;
            }

            void action.callback(this.payload);
        },
    },
});

export default useModal;
