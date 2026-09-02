import RolesForm from "@/Pages/Admin/Roles/Form.vue";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render({
    role = {},
    permissions = [
        {
            id: 7,
            group_id: 1,
            name: { en: "Manage users" },
            description: { en: "Allows editing users" },
        },
    ],
    groups = [{ id: 1, name: { en: "Administration" } }],
}: {
    role?: Record<string, unknown>;
    permissions?: Array<Record<string, unknown>>;
    groups?: Array<Record<string, unknown>>;
} = {}) {
    return mount(RolesForm, {
        props: {
            role,
            permissions,
            groups,
            languages: ["en"],
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
                FormLayout: {
                    template: "<div><slot /></div>",
                },
                TranslatableFormInput: true,
            },
        },
    });
}

describe("Roles/Form", () => {
    test("adds numeric permission ids without coercing them to strings", async () => {
        const wrapper = render();

        await wrapper.get("#permission-checkbox-7").setValue(true);
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            permissions: Array<number | string>;
        };

        expect(form.permissions).toEqual([7]);
        expect(typeof form.permissions[0]).toBe("number");
    });

    test("removes numeric permission ids when the checkbox is unchecked", async () => {
        const wrapper = render({
            role: {
                permissions: [{ id: 7 }],
            },
        });

        await wrapper.get("#permission-checkbox-7").setValue(false);
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            permissions: Array<number | string>;
        };

        expect(form.permissions).toEqual([]);
    });
});
