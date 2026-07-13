import UserGroupImport from "@/Pages/Admin/UserGroups/Import.vue";

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

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

beforeEach(() => {
    vi.clearAllMocks();
});

function render() {
    return mount(UserGroupImport, {
        props: {
            user_group: {
                id: "7",
                title: { en: "Researchers" },
            },
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                DatePicker: {
                    name: "DatePickerStub",
                    props: ["modelValue", "inputId", "disabled"],
                    emits: ["update:modelValue"],
                    template: '<div :data-test="inputId"></div>',
                },
                FormAction: {
                    name: "FormActionStub",
                    props: ["form"],
                    template: '<div data-test="form-action"></div>',
                },
                FormLabel: true,
                FormLayout: {
                    props: ["title", "description"],
                    template: "<div><slot /></div>",
                },
                FormValidationError: true,
                InputGroup: {
                    template: "<div><slot /></div>",
                },
                InputNumber: {
                    name: "InputNumberStub",
                    props: ["modelValue", "inputId", "disabled"],
                    emits: ["update:modelValue"],
                    template: '<div :data-test="inputId"></div>',
                },
                InputText: {
                    props: ["modelValue", "id", "disabled"],
                    emits: ["update:modelValue"],
                    template:
                        '<input :id="id" :value="modelValue" :disabled="disabled" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                },
                Select: {
                    name: "SelectStub",
                    props: ["modelValue", "options", "optionLabel", "optionValue", "disabled"],
                    emits: ["update:modelValue"],
                    template: '<div data-test="valid-until-unit"></div>',
                },
                Textarea: {
                    props: ["modelValue", "id"],
                    emits: ["update:modelValue"],
                    template:
                        '<textarea :id="id" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
                },
            },
        },
    });
}

describe("UserGroups/Import", () => {
    test("syncs valid-from text and date into the submitted form state", async () => {
        const wrapper = render();
        const date = new Date("2026-03-05T00:00:00Z");

        await wrapper.get("#valid-from-text").setValue("next monday");
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            valid_from_date: string | null;
            valid_from_text: string;
        };

        expect(form.valid_from_text).toBe("next monday");

        const pickers = wrapper.findAllComponents({ name: "DatePickerStub" });
        await pickers[0].vm.$emit("update:modelValue", date);
        await nextTick();

        expect(form.valid_from_date).toBe(date.toDateString());
        expect(wrapper.get("#valid-from-text").attributes("disabled")).toBeDefined();
    });

    test("builds valid-until text and normalizes textarea users into the form payload", async () => {
        const wrapper = render();

        await wrapper.get("#users").setValue("Alice\nBob");
        await nextTick();

        await wrapper.findComponent({ name: "InputNumberStub" }).vm.$emit("update:modelValue", 2);
        await nextTick();
        await wrapper.findComponent({ name: "SelectStub" }).vm.$emit("update:modelValue", "weeks");
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            users: { name: string }[];
            valid_until_text: string | null;
        };

        expect(form.users).toEqual([{ name: "Alice" }, { name: "Bob" }]);
        expect(form.valid_until_text).toBe("2 weeks");
    });
});
