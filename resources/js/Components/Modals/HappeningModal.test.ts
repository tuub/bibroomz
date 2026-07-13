import { useAppStore } from "@/Stores/AppStore";

import { mount } from "@vue/test-utils";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { createPinia, setActivePinia } from "pinia";
import { beforeAll, beforeEach, describe, expect, test, vi } from "vitest";
import type { Component } from "vue";

dayjs.extend(utc);

vi.mock("@/Components/HappeningInfo.vue", () => ({
    default: {
        name: "HappeningInfo",
        props: ["happening"],
        template: '<div data-test="happening-info"></div>',
    },
}));

vi.mock("@/Components/Modals/HappeningForm.vue", () => ({
    default: {
        name: "HappeningForm",
        props: ["happening"],
        emits: ["update-happening", "submit"],
        template: '<div data-test="happening-form"></div>',
    },
}));

let HappeningModal: Component;

beforeAll(async () => {
    HappeningModal = (await import("@/Components/Modals/HappeningModal.vue")).default;
});

beforeEach(() => {
    setActivePinia(createPinia());

    const appStore = useAppStore();
    appStore.setTemporalFormats("de");
});

function render(payload: Record<string, unknown>) {
    return mount(HappeningModal, {
        props: {
            content: {
                description: "Inspect booking",
            },
            payload,
        },
    });
}

describe("HappeningModal", () => {
    test("formats the payload into a happening for the info/form children", () => {
        const wrapper = render({
            id: 5,
            resource: { id: "r1" },
            start: "2026-03-05T10:00:00Z",
            end: "2026-03-05T11:00:00Z",
            user_01: "Alice",
            user_02: "Bob",
            isVerificationRequired: true,
            label: { en: "Study room" },
            editable: true,
        });

        const info = wrapper.getComponent({ name: "HappeningInfo" });
        const form = wrapper.getComponent({ name: "HappeningForm" });

        expect(info.props("happening")).toMatchObject({
            id: 5,
            start: "2026-03-05T10:00:00",
            end: "2026-03-05T11:00:00",
            user_01: "Alice",
            verifier: "Bob",
            label: { en: "Study room" },
        });
        expect(form.props("happening")).toMatchObject({
            id: 5,
            start: "2026-03-05T10:00:00",
            end: "2026-03-05T11:00:00",
        });
    });

    test("re-emits update:payload from the happening form", async () => {
        const wrapper = render({
            start: "2026-03-05T10:00:00Z",
            end: "2026-03-05T11:00:00Z",
            editable: true,
        });

        await wrapper.getComponent({ name: "HappeningForm" }).vm.$emit("update-happening", { id: 9 });

        expect(wrapper.emitted("update:payload")).toEqual([[{ id: 9 }]]);
    });

    test("re-emits submit from the happening form", async () => {
        const wrapper = render({
            start: "2026-03-05T10:00:00Z",
            end: "2026-03-05T11:00:00Z",
            editable: true,
        });

        await wrapper.getComponent({ name: "HappeningForm" }).vm.$emit("submit");

        expect(wrapper.emitted("submit")).toEqual([[]]);
    });

    test("hides the happening form when the payload is not editable", () => {
        const wrapper = render({
            start: "2026-03-05T10:00:00Z",
            end: "2026-03-05T11:00:00Z",
            editable: false,
        });

        expect(wrapper.findComponent({ name: "HappeningForm" }).exists()).toBe(false);
    });
});
