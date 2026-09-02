import HappeningQuotas from "@/Components/HappeningQuotas.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent } from "vue";

const HappeningQuotaStub = defineComponent({
    name: "HappeningQuota",
    props: {
        type: { type: String, required: true },
        value: { type: Number, required: true },
        setting: { type: Number, required: true },
    },
    template: `<div class="quota-stub" :data-type="type" :data-value="value" :data-setting="setting"></div>`,
});

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

describe("HappeningQuotas", () => {
    test("renders only quotas with numeric values and a positive configured limit", () => {
        const appStore = useAppStore();
        const authStore = useAuthStore();

        appStore.resourceGroup = {
            settings: [
                { key: "quota_daily_hours", value: "2" },
                { key: "quota_weekly_happenings", value: 0 },
                { key: "quota_weekly_hours", value: "0" },
            ],
        };
        authStore.quotas = {
            daily_hours: 1.5,
            weekly_happenings: 3,
            weekly_hours: 4,
            invalid: "oops",
        } as unknown as typeof authStore.quotas;

        const wrapper = mount(HappeningQuotas, {
            global: {
                stubs: {
                    HappeningQuota: HappeningQuotaStub,
                },
            },
        });

        const quotas = wrapper.findAllComponents(HappeningQuotaStub);

        expect(quotas).toHaveLength(1);
        expect(quotas[0]!.props()).toEqual({
            type: "daily_hours",
            value: 1.5,
            setting: 2,
        });
    });
});
