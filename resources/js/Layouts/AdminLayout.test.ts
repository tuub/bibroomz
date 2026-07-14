import AdminLayout from "@/Layouts/AdminLayout.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

const MainLayoutStub = {
    name: "MainLayout",
    template: '<div><slot name="breadcrumbs" /><slot /></div>',
};

function render(slotContent = '<div data-test="page-content">Page</div>') {
    return mount(AdminLayout, {
        global: {
            stubs: { MainLayout: MainLayoutStub, AdminBreadcrumbs: true },
        },
        slots: { default: slotContent },
    });
}

describe("AdminLayout", () => {
    test("renders the admin breadcrumbs above the page content inside MainLayout", () => {
        const wrapper = render();
        const html = wrapper.html();

        expect(wrapper.findComponent({ name: "AdminBreadcrumbs" }).exists()).toBe(true);
        expect(html.indexOf("admin-breadcrumbs-stub")).toBeLessThan(html.indexOf('data-test="page-content"'));
    });

    test("forwards the default slot content into MainLayout", () => {
        const wrapper = render('<div data-test="page-content">Page</div>');

        expect(wrapper.find('[data-test="page-content"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="page-content"]').text()).toBe("Page");
    });
});
