import AdminBreadcrumbs from "@/Shared/Navigation/AdminBreadcrumbs.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test, vi } from "vitest";
import { defineComponent, ref } from "vue";

interface BreadcrumbItem {
    label: string;
    url: string | null;
    icon?: string;
}

const mockItems = ref<BreadcrumbItem[]>([]);
const mockHome = ref({ icon: "pi pi-home", url: "/start" });

vi.mock("@/Composables/AdminBreadcrumbs", () => ({
    useAdminBreadcrumbs: () => ({ items: mockItems, home: mockHome }),
}));

vi.mock("@inertiajs/vue3", () => ({
    Link: {
        props: ["href"],
        template: `<a :href="href"><slot /></a>`,
    },
}));

// Mimics just enough of PrimeVue's Breadcrumb to exercise our #item slot template,
// without pulling in the real component's theming/pass-through internals.
const BreadcrumbStub = defineComponent({
    props: {
        home: { type: Object, required: true },
        model: { type: Array, required: true },
    },
    data() {
        return { stubProps: { action: {}, icon: {}, label: {} } };
    },
    template: `
        <nav>
            <div data-test="home-slot">
                <slot name="item" :item="home" :label="home?.label" :props="stubProps" />
            </div>
            <div v-for="item in model" :key="item.label" data-test="item-slot">
                <slot name="item" :item="item" :label="item.label" :props="stubProps" />
            </div>
        </nav>
    `,
});

function render(items: BreadcrumbItem[]) {
    mockItems.value = items;
    return mount(AdminBreadcrumbs, {
        global: { stubs: { Breadcrumb: BreadcrumbStub } },
    });
}

describe("AdminBreadcrumbs", () => {
    test("renders nothing when there are no breadcrumb items", () => {
        const wrapper = render([]);
        expect(wrapper.find("nav").exists()).toBe(false);
    });

    test("passes home and model through to the Breadcrumb component", () => {
        const items = [{ label: "Dashboard", url: "/admin" }];
        const wrapper = render(items);
        const breadcrumb = wrapper.findComponent(BreadcrumbStub);

        expect(breadcrumb.props().home).toEqual(mockHome.value);
        expect(breadcrumb.props().model).toEqual(items);
    });

    test("renders a link for items with a url", () => {
        const wrapper = render([{ label: "Institutions", url: "/admin/institutions" }]);
        const link = wrapper.find('[data-test="item-slot"] a');

        expect(link.exists()).toBe(true);
        expect(link.attributes("href")).toBe("/admin/institutions");
        expect(link.text()).toBe("Institutions");
    });

    test("renders plain text with aria-current for items without a url", () => {
        const wrapper = render([{ label: "Room 101", url: null }]);
        const current = wrapper.find('[data-test="item-slot"] span[aria-current="page"]');

        expect(current.exists()).toBe(true);
        expect(current.text()).toBe("Room 101");
        expect(wrapper.find('[data-test="item-slot"] a').exists()).toBe(false);
    });

    test("renders an icon span when the item defines one", () => {
        const wrapper = render([{ label: "Institutions", url: "/admin/institutions", icon: "pi pi-building" }]);
        expect(wrapper.find('[data-test="item-slot"] a .pi-building').exists()).toBe(true);
    });

    test("omits the icon span when the item has no icon", () => {
        const wrapper = render([{ label: "Institutions", url: "/admin/institutions" }]);
        expect(wrapper.find('[data-test="item-slot"] a span[class^="pi-"]').exists()).toBe(false);
    });

    test("renders multiple crumbs in order", () => {
        const wrapper = render([
            { label: "Institutions", url: "/admin/institutions" },
            { label: "TU Berlin", url: null },
        ]);
        const slots = wrapper.findAll('[data-test="item-slot"]');

        expect(slots).toHaveLength(2);
        expect(slots[0].text()).toBe("Institutions");
        expect(slots[1].text()).toBe("TU Berlin");
    });
});
