import ResourceForm from "@/Pages/Admin/Resources/Form.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

beforeEach(() => {
    vi.clearAllMocks();
});

function render(resource: Record<string, unknown> = {}) {
    return mount(ResourceForm, {
        props: {
            resourceGroup: {
                id: "rg-1",
            },
            resource: {
                id: "resource-1",
                business_hours: [
                    {
                        id: "bh-1",
                        resource_id: "resource-1",
                        start: "08:00",
                        end: "10:00",
                        week_days: [1],
                    },
                ],
                ...resource,
            },
            weekDays: [{ id: 1 }, { id: 2 }, { id: 3 }],
            languages: ["en"],
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                BusinessHourField: {
                    name: "BusinessHourFieldStub",
                    props: ["timeSlot", "index", "isOnly", "daysOfWeek", "errors"],
                    emits: ["remove-business-hour-field", "update-business-hour-field"],
                    template: '<div data-test="business-hour-field"></div>',
                },
                FormAction: {
                    name: "FormActionStub",
                    props: ["form"],
                    template: '<div data-test="form-action"></div>',
                },
                FormInput: true,
                FormLabel: true,
                FormLayout: {
                    template: "<div><slot /></div>",
                },
                FormValidationError: true,
                ToggleSwitch: true,
                TranslatableFormInput: true,
            },
        },
    });
}

function renderWithRealBusinessHourField() {
    return mount(ResourceForm, {
        props: {
            resourceGroup: {
                id: "rg-1",
            },
            resource: {
                id: "resource-1",
                business_hours: [
                    {
                        id: "bh-1",
                        resource_id: "resource-1",
                        start: "08:00",
                        end: "10:00",
                        week_days: [1],
                    },
                ],
            },
            weekDays: [
                { id: 1, key: "monday" },
                { id: 2, key: "tuesday" },
                { id: 3, key: "wednesday" },
            ],
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
                FormInput: true,
                FormLabel: true,
                FormLayout: {
                    template: "<div><slot /></div>",
                },
                FormValidationError: true,
                ToggleSwitch: true,
                TranslatableFormInput: true,
            },
        },
    });
}

describe("Resources/Form", () => {
    test("updates the matching business hour and maps checked weekdays to ids", async () => {
        const wrapper = render();
        const field = wrapper.findComponent({ name: "BusinessHourFieldStub" });

        await field.vm.$emit("update-business-hour-field", {
            id: "bh-1",
            start: "09:00",
            end: "11:00",
            startDate: "2026-03-01",
            endDate: "2026-03-05",
            checkedWeekDays: [2, 3],
        });
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            business_hours: Array<Record<string, unknown>>;
        };

        expect(form.business_hours[0]).toEqual({
            id: "bh-1",
            resource_id: "resource-1",
            start: "09:00",
            end: "11:00",
            start_date: "2026-03-01",
            end_date: "2026-03-05",
            week_days: [2, 3],
        });
    });

    test("ignores a business-hour update when the id is unknown", async () => {
        const wrapper = render();
        const field = wrapper.findComponent({ name: "BusinessHourFieldStub" });

        await field.vm.$emit("update-business-hour-field", {
            id: "missing",
            start: "09:00",
            end: "11:00",
            startDate: "2026-03-01",
            endDate: "2026-03-05",
            checkedWeekDays: [2, 3],
        });
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            business_hours: Array<Record<string, unknown>>;
        };

        expect(form.business_hours).toEqual([
            {
                id: "bh-1",
                resource_id: "resource-1",
                start: "08:00",
                end: "10:00",
                week_days: [1],
            },
        ]);
    });

    test("adds a new business-hour draft with the current resource id", async () => {
        vi.spyOn(Date, "now").mockReturnValue(1234);
        const wrapper = render();

        await wrapper.get("a").trigger("click");

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            business_hours: Array<Record<string, unknown>>;
        };

        expect(form.business_hours).toContainEqual({
            id: "1234",
            resource_id: "resource-1",
        });
    });

    test("removes the emitted business-hour row instead of always removing the last one", async () => {
        const wrapper = render({
            business_hours: [
                {
                    id: "bh-1",
                    resource_id: "resource-1",
                    start: "08:00",
                    end: "10:00",
                    week_days: [1],
                },
                {
                    id: "bh-2",
                    resource_id: "resource-1",
                    start: "10:00",
                    end: "12:00",
                    week_days: [2],
                },
            ],
        });
        const fields = wrapper.findAllComponents({ name: "BusinessHourFieldStub" });

        await fields[0]!.vm.$emit("remove-business-hour-field", {
            time_slot: {
                id: "bh-1",
            },
        });
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            business_hours: Array<Record<string, unknown>>;
        };

        expect(form.business_hours).toEqual([
            {
                id: "bh-2",
                resource_id: "resource-1",
                start: "10:00",
                end: "12:00",
                week_days: [2],
            },
        ]);
    });

    test("accepts the real BusinessHourField payload as primitive values", async () => {
        const wrapper = renderWithRealBusinessHourField();

        await wrapper.get("#businessHourStart-0").setValue("09:00");
        await wrapper.get("#businessHourStart-0").trigger("change");
        await nextTick();

        const form = wrapper.findComponent({ name: "FormActionStub" }).props("form") as {
            business_hours: Array<Record<string, unknown>>;
        };

        expect(form.business_hours[0]?.start).toBe("09:00");
        expect(typeof form.business_hours[0]?.start).toBe("string");
    });
});
