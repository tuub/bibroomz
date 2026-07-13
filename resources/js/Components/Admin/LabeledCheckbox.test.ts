import LabeledCheckbox from "@/Components/Admin/LabeledCheckbox.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

function render(props: Record<string, unknown> = {}) {
    return mount(LabeledCheckbox, {
        props: {
            value: 7,
            checked: false,
            name: "permission",
            label: "Manage users",
            description: "Allows editing users",
            ...props,
        },
    });
}

describe("LabeledCheckbox", () => {
    test("renders the generated ids from name and value", () => {
        const wrapper = render();
        const input = wrapper.get("input");
        const description = wrapper.get("p");

        expect(input.attributes("id")).toBe("permission-checkbox-7");
        expect(input.attributes("aria-describedby")).toBe("permission-checkbox-text-7");
        expect(description.attributes("id")).toBe("permission-checkbox-text-7");
    });

    test("emits the checked payload on change", async () => {
        const wrapper = render();

        await wrapper.get("input").setValue(true);

        expect(wrapper.emitted("update-checked")).toEqual([[{ value: 7, checked: true }]]);
    });
});
