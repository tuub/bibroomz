import Breadcrumbs from "@/Shared/Breadcrumbs.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent, ref } from "vue";

const pageProps = ref<{ route?: string }>({});

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: pageProps.value }),
}));

const BreadcrumbStub = defineComponent({
    name: "BreadcrumbStub",
    props: {
        home: { type: Object, required: true },
        model: { type: Array, required: true },
    },
    template: `
        <div>
            <div data-test="home">{{ home.to }}</div>
            <div v-for="item in model" :key="item.label" data-test="crumb">
                {{ item.label }}|{{ item.to }}
            </div>
        </div>
    `,
});

beforeEach(() => {
    pageProps.value = {};
});

function render(route?: string) {
    pageProps.value = { route };

    return mount(Breadcrumbs, {
        global: {
            provide: {
                ziggyRoute: (name: string) => `/${name}`,
            },
            stubs: {
                Breadcrumb: BreadcrumbStub,
            },
        },
    });
}

describe("Breadcrumbs", () => {
    test("handles a missing route without rendering crumbs", () => {
        const wrapper = render();

        expect(wrapper.findAll('[data-test="crumb"]')).toHaveLength(0);
        expect(wrapper.find('[data-test="home"]').text()).toBe("/start");
    });

    test("maps known routes into breadcrumb items", () => {
        const wrapper = render("privacy_statement");

        const crumbs = wrapper.findAll('[data-test="crumb"]');

        expect(crumbs).toHaveLength(1);
        expect(crumbs[0]!.text()).toBe("PRIVACY|/privacy_statement");
    });
});
