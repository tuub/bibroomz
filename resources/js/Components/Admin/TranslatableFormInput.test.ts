import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test, vi } from "vitest";
import { defineComponent, h } from "vue";

const TranslatableFormFieldStub = defineComponent({
    name: "TranslatableFormFieldStub",
    props: {
        languages: {
            type: Array,
            default: (): string[] => [],
        },
    },
    setup(props, { slots }) {
        return () =>
            h(
                "div",
                ((props.languages as string[]) ?? []).map((language) =>
                    h("div", { key: language }, slots.default?.({ language }) ?? []),
                ),
            );
    },
});

function render(props: Record<string, unknown> = {}) {
    return mount(TranslatableFormInput, {
        props: {
            modelValue: { en: "" },
            field: "title",
            fieldKey: "admin.resources.form.fields.title",
            languages: ["en"],
            ...props,
        },
        global: {
            stubs: {
                TranslatableFormField: TranslatableFormFieldStub,
            },
        },
    });
}

describe("TranslatableFormInput", () => {
    test("renders an input by default and emits the updated translatable object", async () => {
        const wrapper = render();

        await wrapper.get("#title-en").setValue("Study room");

        expect(wrapper.emitted("update:model-value")).toEqual([[{ en: "Study room" }]]);
    });

    test("warns and falls back to a text input for an unsupported type value", () => {
        const consoleSpy = vi.spyOn(console, "warn").mockImplementation(() => undefined);

        const wrapper = render({ type: "unsupported-type" });

        expect(consoleSpy).toHaveBeenCalledWith(
            'TranslatableFormInput: unsupported type "unsupported-type"; falling back to a text input.',
        );
        expect(wrapper.find("input").exists()).toBe(true);
        expect(wrapper.find("textarea").exists()).toBe(false);

        consoleSpy.mockRestore();
    });

    test("renders a textarea when requested", () => {
        const wrapper = render({
            type: "textarea",
            rows: "4",
        });

        const textarea = wrapper.get("textarea");
        expect(textarea.attributes("rows")).toBe("4");
        expect(wrapper.find("input").exists()).toBe(false);
    });

    test("normalizes an array model value into an object before emitting", async () => {
        const wrapper = render({
            modelValue: [],
            type: "textarea",
        });

        await wrapper.get("#title-en").setValue("Quiet floor");

        expect(wrapper.emitted("update:model-value")).toEqual([[{ en: "Quiet floor" }]]);
    });
});
