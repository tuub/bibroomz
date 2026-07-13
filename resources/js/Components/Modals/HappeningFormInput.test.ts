import HappeningFormInput from "@/Components/Modals/HappeningFormInput.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

describe("HappeningFormInput", () => {
    test("defaults the input value to an empty string", () => {
        const wrapper = mount(HappeningFormInput);

        expect((wrapper.find("input").element as HTMLInputElement).value).toBe("");
    });

    test("emits the updated string value on input", async () => {
        const wrapper = mount(HappeningFormInput, {
            props: {
                input: "Initial",
            },
        });

        await wrapper.find("input").setValue("Updated");

        expect(wrapper.emitted("update:input")).toEqual([["Updated"]]);
    });
});
