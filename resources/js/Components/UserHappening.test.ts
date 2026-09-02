/* eslint-disable vue/one-component-per-file */
import UserHappening from "@/Components/UserHappening.vue";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent } from "vue";

const UserHappeningDataStub = defineComponent({
    name: "UserHappeningData",
    props: {
        happening: { type: Object, required: true },
    },
    template: '<div data-test="happening-data"></div>',
});

const FancyDateStub = defineComponent({
    name: "FancyDate",
    props: {
        happening: { type: Object, required: true },
        cssClass: { type: String, required: true },
    },
    template: '<div data-test="fancy-date"></div>',
});

const SidebarLabelStub = defineComponent({
    name: "SidebarLabel",
    props: {
        label: { type: String, required: true },
        severity: { type: String, required: false, default: "info" },
    },
    template: '<div data-test="sidebar-label">{{ label }}|{{ severity }}</div>',
});

const UserHappeningStatusStub = defineComponent({
    name: "UserHappeningStatus",
    props: {
        label: { type: String, required: true },
    },
    template: '<div data-test="happening-status">{{ label }}</div>',
});

const UserHappeningActionsStub = defineComponent({
    name: "UserHappeningActions",
    props: {
        happening: { type: Object, required: true },
    },
    template: '<div data-test="happening-actions"></div>',
});

function render(happening: Record<string, unknown>) {
    return mount(UserHappening, {
        props: { happening },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                UserHappeningData: UserHappeningDataStub,
                FancyDate: FancyDateStub,
                SidebarLabel: SidebarLabelStub,
                UserHappeningStatus: UserHappeningStatusStub,
                UserHappeningActions: UserHappeningActionsStub,
            },
        },
    });
}

beforeEach(() => {
    setActivePinia(createPinia());
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2026-03-05T10:30:00Z"));
});

afterEach(() => {
    vi.useRealTimers();
});

describe("UserHappening", () => {
    test("translates resource fields before passing them to child components", () => {
        const wrapper = render({
            id: 1,
            start: "2026-03-05T11:00:00Z",
            end: "2026-03-05T12:00:00Z",
            isVerified: true,
            resource: {
                title: { en: "Study Room" },
                location: { en: "Level 2" },
                description: { en: "Quiet area" },
                resourceGroup: { en: "Rooms" },
                institution: { en: "TU Berlin" },
            },
        });

        const dataProps = wrapper.findComponent(UserHappeningDataStub).props("happening") as {
            resource: Record<string, unknown>;
        };

        expect(dataProps.resource).toMatchObject({
            title: "Study Room",
            location: "Level 2",
            description: "Quiet area",
            resourceGroup: "Rooms",
            institution: { en: "TU Berlin" },
        });
    });

    test("marks future verified happenings as bookings", () => {
        const wrapper = render({
            id: 1,
            start: "2026-03-05T11:00:00Z",
            end: "2026-03-05T12:00:00Z",
            isVerified: true,
            resource: {},
        });

        expect(wrapper.findComponent(FancyDateStub).props("cssClass")).toBe("status-booking");
        expect(wrapper.find('[data-test="sidebar-label"]').text()).toBe("user_happenings.item.verified|success");
        expect(wrapper.find('[data-test="happening-status"]').text()).toBe("user_happenings.item.future_happening");
    });

    test("marks past happenings as over and hides verification labels", () => {
        const wrapper = render({
            id: 1,
            start: "2026-03-05T08:00:00Z",
            end: "2026-03-05T09:00:00Z",
            isVerified: false,
            resource: {},
        });

        expect(wrapper.findComponent(FancyDateStub).props("cssClass")).toBe("over");
        expect(wrapper.find('[data-test="happening-status"]').text()).toBe("user_happenings.item.past_happening");
        expect(wrapper.find('[data-test="sidebar-label"]').exists()).toBe(false);
    });
});
