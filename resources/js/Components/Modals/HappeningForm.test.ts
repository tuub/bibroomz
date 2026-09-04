import HappeningForm from "@/Components/Modals/HappeningForm.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useHappeningStore } from "@/Stores/HappeningStore";
import { useModal } from "@/Stores/Modal";

import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import type { PropType } from "vue";

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

const axiosMock = {
    get: vi.fn(),
    post: vi.fn(),
};

type SelectOption = Record<string, unknown>;
type SelectStubProps = {
    optionLabel?: string;
    optionValue?: string;
    optionDisabled?: string;
};
type SelectStubEmit = (event: string, ...args: unknown[]) => void;

const FormLabelStub = {
    name: "FormLabelStub",
    props: {
        field: {
            type: String,
            required: true,
        },
        fieldKey: {
            type: String,
            required: true,
        },
        asLabelledby: {
            type: Boolean,
            default: false,
        },
    },
    template:
        '<span v-if="asLabelledby" :id="`${field}-label`">{{ fieldKey }}</span><label v-else :for="field">{{ fieldKey }}</label>',
};

const SelectStub = {
    name: "SelectStub",
    props: {
        modelValue: {
            type: [String, Number] as PropType<string | number | null>,
            default: null,
        },
        inputId: {
            type: String,
            default: "",
        },
        name: {
            type: String,
            default: "",
        },
        options: {
            type: Array as PropType<SelectOption[]>,
            default: () => [],
        },
        optionLabel: {
            type: String,
            default: "",
        },
        optionValue: {
            type: String,
            default: "",
        },
        optionDisabled: {
            type: String,
            default: "",
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue", "change"],
    setup(props: SelectStubProps, { emit }: { emit: SelectStubEmit }) {
        const getOptionValue = (option: SelectOption): string | number => {
            const value = props.optionValue ? option[props.optionValue] : option;

            return typeof value === "number" ? value : String(value);
        };

        const getOptionLabel = (option: SelectOption): string => {
            const label = props.optionLabel ? option[props.optionLabel] : option;

            return String(label);
        };

        const isOptionDisabled = (option: SelectOption): boolean =>
            props.optionDisabled ? Boolean(option[props.optionDisabled]) : false;

        const changeValue = (event: Event): void => {
            const value = (event.target as HTMLSelectElement).value;

            emit("update:modelValue", value);
            emit("change", { originalEvent: event, value });
        };

        return { changeValue, getOptionLabel, getOptionValue, isOptionDisabled };
    },
    template: `
        <select
            :id="inputId"
            :name="name"
            :value="modelValue"
            :disabled="disabled"
            :aria-busy="String(loading)"
            @change="changeValue"
        >
            <option
                v-for="option in options"
                :key="getOptionValue(option)"
                :value="getOptionValue(option)"
                :disabled="isOptionDisabled(option)"
            >
                {{ getOptionLabel(option) }}
            </option>
        </select>
    `,
};

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    const appStore = useAppStore();
    appStore.resourceGroup = {
        slug: "rooms",
        institution: { slug: "tu-berlin" },
    };
    appStore.settings = {
        resource_group: {
            is_label_enabled: 0,
        },
    };
    appStore.supportedLocales = ["en"];

    const authStore = useAuthStore();
    authStore.isAdmin = false;

    axiosMock.post.mockResolvedValue({
        data: {
            start: [
                { time: "09:00", label: "09:00", is_selected: true, is_disabled: false },
                { time: "09:30", label: "09:30", is_selected: false, is_disabled: false },
            ],
            end: [{ time: "10:00", label: "10:00", is_selected: true, is_disabled: false }],
        },
    });
    axiosMock.get.mockResolvedValue({ data: [] });
    globalThis.axios = axiosMock as unknown as typeof axios;
});

function render({
    happening,
}: {
    happening?: Record<string, unknown>;
} = {}) {
    return mount(HappeningForm, {
        props: {
            happening: {
                id: 5,
                resource: { id: 7 },
                start: "09:00",
                end: "10:00",
                label: {},
                verifier: "",
                isVerificationRequired: true,
                ...happening,
            },
        },
        global: {
            provide: {
                ziggyRoute: () => "/resource/time-slots",
            },
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                FormLabel: FormLabelStub,
                ModalAlert: {
                    template: '<button data-test="close-alert" @click="$emit(\'close\')">close</button>',
                },
                Select: SelectStub,
            },
        },
    });
}

describe("HappeningForm", () => {
    test("keeps the time slot fields mounted and labelled while their options are loading", () => {
        const wrapper = render();

        wrapper.get("#start-label");
        expect(wrapper.get("#start").attributes("disabled")).toBeDefined();
        expect(wrapper.get("#start").attributes("aria-busy")).toBe("true");
        expect(wrapper.find('label[for="start"]').exists()).toBe(false);

        wrapper.get("#end-label");
        expect(wrapper.get("#end").attributes("disabled")).toBeDefined();
        expect(wrapper.get("#end").attributes("aria-busy")).toBe("true");
        expect(wrapper.find('label[for="end"]').exists()).toBe(false);
    });

    test("keeps happening start/end as strings when time slots change", async () => {
        const wrapper = render();

        await flushPromises();

        axiosMock.post.mockResolvedValueOnce({
            data: {
                start: [
                    { time: "09:00", label: "09:00", is_selected: false, is_disabled: false },
                    { time: "09:30", label: "09:30", is_selected: true, is_disabled: false },
                ],
                end: [{ time: "10:00", label: "10:00", is_selected: true, is_disabled: false }],
            },
        });

        await wrapper.find("#start").setValue("09:30");
        await flushPromises();
        await wrapper.find("#verifier").setValue("Alice");

        const emissions = wrapper.emitted("update-happening");
        const payload = emissions?.at(-1)?.[0] as {
            start: unknown;
            end: unknown;
        };

        expect(payload.start).toBe("09:30");
        expect(payload.end).toBe("10:00");
        expect(typeof payload.start).toBe("string");
        expect(typeof payload.end).toBe("string");
    });

    test("reflects a server-side time slot correction that disagrees with the user's selection", async () => {
        const wrapper = render();
        await flushPromises();

        axiosMock.post.mockResolvedValueOnce({
            data: {
                start: [
                    { time: "09:00", label: "09:00", is_selected: true, is_disabled: false },
                    { time: "09:30", label: "09:30", is_selected: false, is_disabled: false },
                ],
                end: [{ time: "10:00", label: "10:00", is_selected: true, is_disabled: false }],
            },
        });

        await wrapper.find("#start").setValue("09:30");
        await flushPromises();

        await wrapper.find("#verifier").setValue("Alice");

        const emissions = wrapper.emitted("update-happening");
        const payload = emissions?.at(-1)?.[0] as { start: unknown };

        expect(payload.start).toBe("09:00");
    });

    test("fetches admin users for the create flow and passes them to the select", async () => {
        const authStore = useAuthStore();
        authStore.isAdmin = true;
        axiosMock.get.mockResolvedValueOnce({ data: [{ id: 9, name: "Alice" }] });

        const wrapper = render({
            happening: {
                id: undefined,
                verifier: undefined,
                isVerificationRequired: false,
            },
        });

        await flushPromises();

        expect(axiosMock.get).toHaveBeenCalledWith("https://rooms.example.com/api/admin/user/users");
        expect(wrapper.get("#user_id_01").findAll("option")).toHaveLength(1);
    });

    test("clears the happening store error when the alert is closed", async () => {
        const happeningStore = useHappeningStore();
        happeningStore.error = {
            data: {
                message: "Broken",
            },
        };

        const wrapper = render();

        await wrapper.get('[data-test="close-alert"]').trigger("click");

        expect(happeningStore.error).toBeNull();
    });

    test("closes the modal and re-checks auth when loading time slots fails", async () => {
        const authStore = useAuthStore();
        const modalStore = useModal();
        const checkSpy = vi.spyOn(authStore, "check").mockResolvedValue(undefined);
        const closeSpy = vi.spyOn(modalStore, "close");
        const consoleSpy = vi.spyOn(console, "log").mockImplementation(() => undefined);

        axiosMock.post.mockRejectedValueOnce(new Error("boom"));

        render();
        await flushPromises();

        expect(closeSpy).toHaveBeenCalledOnce();
        expect(checkSpy).toHaveBeenCalledOnce();

        consoleSpy.mockRestore();
    });
});
