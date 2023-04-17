import { markRaw } from "vue";
import { defineStore } from "pinia";

export type Modal = {
    isOpen: boolean,
    view: object,
    content: object,
    payload: object,
    actions?: ModalAction[],
};

export type ModalAction = {
    label: string,
    callback: (props?: any) => void,
};

export const useModal = defineStore("modal", {
    state: (): Modal => ({
        isOpen: false,
        view: {},
        content: {},
        payload: {},
        actions: [],
    }),
    actions: {
        open(view: Object, content: Object, payload: Object, actions?: ModalAction[]) {
            console.log('*** MODAL OPENS ***');
            console.log('-> View');
            console.log(view);
            console.log('-> Content');
            console.log(content);
            console.log('-> Payload');
            console.log(payload);
            console.log('-> Actions');
            console.log(actions);

            this.isOpen = true;
            // using markRaw to avoid over performance as reactive is not required
            this.view = markRaw(view);
            this.content = content;
            this.payload = payload;
            this.actions = actions;
        },
        close() {
            this.isOpen = false;
            this.actions = [];
            this.view = {};
            this.content = {};
            this.payload = {};
        },
    },
});

export default useModal;
