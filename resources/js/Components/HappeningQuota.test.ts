import HappeningQuota from "@/Components/HappeningQuota.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

function render(props: { type: string; value: number; setting: number }) {
    return mount(HappeningQuota, {
        props,
        global: {
            mocks: {
                $tChoice: (key: string, count: unknown) => `${key}|${String(count)}|${typeof count}`,
            },
        },
    });
}

describe("HappeningQuota", () => {
    test("formats fractional remaining time and keeps the pluralization count numeric", () => {
        const wrapper = render({
            type: "daily_hours",
            value: 0.5,
            setting: 2,
        });

        expect(wrapper.text()).toContain("1:30");
        expect(wrapper.text()).toContain("quota.daily_hours.label|1.5|number");
    });

    test("formats whole-hour remaining time without minutes", () => {
        const wrapper = render({
            type: "weekly_hours",
            value: 1,
            setting: 3,
        });

        expect(wrapper.text()).toContain("2");
        expect(wrapper.text()).toContain("quota.weekly_hours.label|2|number");
    });

    test("prefixes the overage with a negative sign when the quota is exceeded by less than an hour", () => {
        const wrapper = render({
            type: "daily_hours",
            value: 2.5,
            setting: 2,
        });

        expect(wrapper.get(".bg-gray-600").text()).toBe("-0:30");
    });
});
