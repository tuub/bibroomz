import SystemNotificationList from "@/Components/SystemNotificationList.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

describe("SystemNotificationList", () => {
    test("renders nothing when notifications are omitted", () => {
        const wrapper = mount(SystemNotificationList);

        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });

    test("renders every notification message", () => {
        const wrapper = mount(SystemNotificationList, {
            props: {
                notifications: [{ message: "Maintenance tonight" }, { title: "TU Berlin", message: "Bring your card" }],
            },
        });

        const sections = wrapper.findAll('[role="status"]');

        expect(sections).toHaveLength(2);
        expect(sections[0].text()).toContain("Maintenance tonight");
        expect(sections[1].text()).toContain("Bring your card");
    });
});
