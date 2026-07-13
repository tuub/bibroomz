import BusinessHourField from "@/Components/Admin/BusinessHourField.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

function render(props: Record<string, unknown> = {}) {
    return mount(BusinessHourField, {
        props: {
            timeSlot: {
                id: "bh-1",
                start: "08:00",
                end: "10:00",
                start_date: "2026-03-01",
                end_date: "2026-03-05",
                week_days: [3, 1],
            },
            index: 0,
            daysOfWeek: [
                { id: 1, key: "monday" },
                { id: 2, key: "tuesday" },
                { id: 3, key: "wednesday" },
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

describe("BusinessHourField", () => {
    test("emits primitive values instead of refs when a field changes", async () => {
        const wrapper = render();

        await wrapper.get("#businessHourStart-0").setValue("09:00");

        const payload = wrapper.emitted("update-business-hour-field")?.[0]?.[0] as Record<string, unknown>;

        expect(payload).toEqual({
            id: "bh-1",
            start: "09:00",
            end: "10:00",
            startDate: "2026-03-01",
            endDate: "2026-03-05",
            checkedWeekDays: [1, 3],
        });
    });

    test("emits the remove event with the original time slot", async () => {
        const wrapper = render({ isOnly: false });

        await wrapper.get("a").trigger("click");

        expect(wrapper.emitted("remove-business-hour-field")).toEqual([
            [
                {
                    time_slot: {
                        id: "bh-1",
                        start: "08:00",
                        end: "10:00",
                        start_date: "2026-03-01",
                        end_date: "2026-03-05",
                        week_days: [3, 1],
                    },
                },
            ],
        ]);
    });

    test("hides the remove link when it is the only business-hour row", () => {
        const wrapper = render({ isOnly: true });

        expect(wrapper.find("a").exists()).toBe(false);
    });
});
