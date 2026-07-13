import FormSelect from "@/Shared/Form/FormSelect.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

function render(props: Record<string, unknown> = {}) {
    return mount(FormSelect, {
        props: {
            modelValue: "",
            field: "role",
            fieldKey: "admin.roles.form.fields.role",
            options: [
                { key: 1, value: "reader", label: "Reader" },
                { key: 2, value: "writer", label: "Writer" },
            ],
            ...props,
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                FormLabel: true,
                FormValidationError: true,
            },
        },
    });
}

describe("FormSelect", () => {
    test("renders the default placeholder option when none is provided", () => {
        const wrapper = render();
        const options = wrapper.findAll("option");

        expect(options[0].element.value).toBe("");
        expect(options[0].text()).toBe("admin.general.form.choose");
        expect(options[1].text()).toBe("Reader");
        expect(options[2].text()).toBe("Writer");
    });

    test("respects a custom placeholder value", () => {
        const wrapper = render({
            placeholder: {
                value: "any",
            },
        });

        expect(wrapper.find("option").element.value).toBe("any");
    });

    test("emits the updated model value when the selection changes", async () => {
        const wrapper = render();

        await wrapper.get("select").setValue("writer");

        expect(wrapper.emitted("update:modelValue")).toEqual([["writer"]]);
    });

    test("renders the validation error only when one is provided", () => {
        const withoutError = render();
        const withError = render({ error: "Required" });

        expect(withoutError.findComponent({ name: "FormValidationError" }).exists()).toBe(false);
        expect(withError.findComponent({ name: "FormValidationError" }).exists()).toBe(true);
    });
});
