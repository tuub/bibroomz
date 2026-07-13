import LoginModal from "@/Components/Modals/LoginModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test } from "vitest";

beforeEach(() => {
    setActivePinia(createPinia());

    const appStore = useAppStore();
    appStore.globalSystemNotification = "Maintenance";

    const authStore = useAuthStore();
    authStore.error = null;
    authStore.isProcessingLogin = false;
});

function render() {
    return mount(LoginModal, {
        props: {
            payload: {
                username: "",
                password: "",
            },
            content: {
                description: "Please sign in",
            },
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                FormValidationError: true,
                ModalAlert: {
                    name: "ModalAlertStub",
                    props: ["error"],
                    emits: ["close"],
                    template: '<button data-test="alert" @click="$emit(\'close\')">{{ error }}</button>',
                },
                Spinner: true,
                SystemNotificationList: true,
            },
        },
    });
}

describe("LoginModal", () => {
    test("updates the payload model through the form inputs", async () => {
        const wrapper = render();

        await wrapper.get("#username").setValue("alice");
        await wrapper.get("#password").setValue("secret");

        expect((wrapper.props("payload") as { username: string }).username).toBe("alice");
        expect((wrapper.props("payload") as { password: string }).password).toBe("secret");
    });

    test("emits submit when the form is submitted", async () => {
        const wrapper = render();

        await wrapper.get("form").trigger("submit");

        expect(wrapper.emitted("submit")).toEqual([[]]);
    });

    test("renders the spinner while login is processing", () => {
        const authStore = useAuthStore();
        authStore.isProcessingLogin = true;

        const wrapper = render();

        expect(wrapper.findComponent({ name: "Spinner" }).exists()).toBe(true);
    });

    test("shows the modal alert for a general error and clears it on close", async () => {
        const authStore = useAuthStore();
        authStore.error = {
            data: {
                message: "Invalid credentials",
            },
        };

        const wrapper = render();

        expect(wrapper.get('[data-test="alert"]').text()).toContain("Invalid credentials");

        await wrapper.get('[data-test="alert"]').trigger("click");

        expect(authStore.error).toBeNull();
    });
});
