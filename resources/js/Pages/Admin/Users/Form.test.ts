import UsersForm from "@/Pages/Admin/Users/Form.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

const appStoreMock = {
    translate: vi.fn((value?: string | Record<string, string>) => {
        if (typeof value === "string") {
            return value;
        }

        return value?.en ?? "";
    }),
};

vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = "";
});

function render({
    user = {
        id: "5",
        is_system_user: true,
        roles: [
            { institution_id: "1", role_id: "10" },
            { institution_id: "1", role_id: "11" },
            { institution_id: "2", role_id: "12" },
        ],
    },
    isSystemUser = false,
    isSetPassword = false,
}: {
    user?: Record<string, unknown>;
    isSystemUser?: boolean;
    isSetPassword?: boolean;
} = {}) {
    return mount(UsersForm, {
        attachTo: document.body,
        props: {
            user,
            institutions: [
                { id: "1", title: { en: "Alpha" } },
                { id: "2", title: { en: "Beta" } },
            ],
            roles: [
                { id: "10", name: { en: "Reader" } },
                { id: "11", name: { en: "Writer" } },
                { id: "12", name: { en: "Admin" } },
            ],
            is_system_user: isSystemUser,
            is_set_password: isSetPassword,
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                FormAction: {
                    name: "FormActionStub",
                    props: ["form"],
                    template: '<div data-test="form-action"></div>',
                },
                FormInput: {
                    props: ["modelValue", "field", "type"],
                    emits: ["update:modelValue"],
                    template:
                        '<input :id="field" :type="type || \'text\'" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                },
                FormLabel: true,
                FormLayout: {
                    template: "<div><slot /></div>",
                },
                FormValidationError: true,
                MultiSelect: {
                    name: "MultiSelectStub",
                    props: ["modelValue", "options", "invalid"],
                    emits: ["update:modelValue"],
                    template: '<div data-test="multi-select"></div>',
                },
                ToggleSwitch: {
                    props: ["modelValue", "inputId", "disabled"],
                    emits: ["update:modelValue"],
                    template:
                        '<input :id="inputId" type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit(\'update:modelValue\', !modelValue)" />',
                },
            },
        },
    });
}

describe("Users/Form", () => {
    test("groups existing user roles per institution for the MultiSelect inputs", () => {
        const wrapper = render();
        const selects = wrapper.findAllComponents({ name: "MultiSelectStub" });

        expect(selects).toHaveLength(2);
        expect(selects[0].props("modelValue")).toEqual(["10", "11"]);
        expect(selects[1].props("modelValue")).toEqual(["12"]);
    });

    test("syncs changed role selections back into the form payload", async () => {
        const wrapper = render();
        const selects = wrapper.findAllComponents({ name: "MultiSelectStub" });

        await selects[0].vm.$emit("update:modelValue", ["10", "99"]);
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            roles: { institution_id: string; role_id: string[] }[];
        };

        expect(form.roles).toEqual([
            { institution_id: "1", role_id: ["10", "99"] },
            { institution_id: "2", role_id: ["12"] },
        ]);
    });

    test("toggles rendered password inputs even when one password field is absent", async () => {
        const wrapper = render({
            user: {
                is_system_user: true,
            },
            isSystemUser: true,
            isSetPassword: true,
        });

        expect((wrapper.get("#password").element as HTMLInputElement).type).toBe("password");
        expect((wrapper.get("#password_confirm").element as HTMLInputElement).type).toBe("password");
        expect(wrapper.find("#current_password").exists()).toBe(false);

        await wrapper.get("button").trigger("click");

        expect((wrapper.get("#password").element as HTMLInputElement).type).toBe("text");
        expect((wrapper.get("#password_confirm").element as HTMLInputElement).type).toBe("text");
    });
});
