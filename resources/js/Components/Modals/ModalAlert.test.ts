import ModalAlert from "@/Components/Modals/ModalAlert.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

describe("ModalAlert", () => {
    test("renders the error message", () => {
        const wrapper = mount(ModalAlert, {
            props: {
                error: "Something went wrong",
            },
        });

        expect(wrapper.text()).toContain("Something went wrong");
    });

    test("emits close when the dismiss button is clicked", async () => {
        const wrapper = mount(ModalAlert, {
            props: {
                error: "Something went wrong",
            },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("close")).toEqual([[]]);
    });
});
