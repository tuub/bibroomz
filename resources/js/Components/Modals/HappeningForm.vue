<template>
    <form class="space-y-3">
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <FormLabel field="start" field-key="modal.form.fields.start"></FormLabel>
                <Spinner v-if="isLoading" size="small"></Spinner>
                <select
                    v-else
                    id="start"
                    v-model="start_time_slot_selected"
                    name="start"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                    @change="syncTimeSlotValues($event, start_time_slot_selected, end_time_slot_selected)"
                    @input="$emit('update-happening', happening)"
                >
                    <option
                        v-for="start_time_slot in start_time_slots"
                        :key="start_time_slot.time"
                        :value="start_time_slot.time"
                        :selected="start_time_slot.is_selected"
                        :disabled="start_time_slot.is_disabled"
                    >
                        {{ start_time_slot.label }}
                    </option>
                </select>
            </div>

            <div>
                <FormLabel field="end" field-key="modal.form.fields.end"></FormLabel>
                <Spinner v-if="isLoading" size="small"></Spinner>
                <select
                    v-else
                    id="end"
                    v-model="end_time_slot_selected"
                    name="end"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                    @change="syncTimeSlotValues($event, start_time_slot_selected, end_time_slot_selected)"
                    @input="$emit('update-happening', happening)"
                >
                    <option
                        v-for="end_time_slot in end_time_slots"
                        :key="end_time_slot.time"
                        :value="end_time_slot.time"
                        :selected="end_time_slot.is_selected"
                        :disabled="end_time_slot.is_disabled"
                    >
                        {{ end_time_slot.label }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="isAdminCreateMode">
            <FormLabel field="user_id_01" field-key="modal.form.fields.user_id_01"></FormLabel>
            <Select
                v-model="happening.user_id_01"
                input-id="user_id_01"
                :options="formUsers"
                option-label="name"
                option-value="id"
                :placeholder="$t('modal.form.fields.user_id_01.placeholder')"
                filter
                class="w-full"
                @change="$emit('update-happening', happening)"
            />
        </div>

        <div v-if="happening.isVerificationRequired && !can('no_verifier')">
            <FormLabel field="verifier" field-key="modal.form.fields.verifier"></FormLabel>
            <input
                id="verifier"
                v-model="happening.verifier"
                type="text"
                name="verifier"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('modal.form.fields.verifier.placeholder')"
                :disabled="!!happening.id"
                @input="$emit('update-happening', happening)"
                @keypress.enter.prevent="$emit('submit')"
            />
        </div>

        <div v-if="isLabelEnabled" class="grid gap-2 sm:grid-cols-2">
            <div v-for="locale in appStore.supportedLocales" :key="locale" class="row-span-2 grid grid-rows-subgrid">
                <FormLabel
                    :field="`label-${locale}`"
                    :field-key="`modal.form.fields.label.${locale}`"
                    :language="locale"
                ></FormLabel>
                <input
                    :id="`label-${locale}`"
                    v-model="happening.label[locale]"
                    type="text"
                    name="label"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                    :placeholder="$t(`modal.form.fields.label.${locale}.placeholder`)"
                    @input="$emit('update-happening', happening)"
                    @keypress.enter.prevent="$emit('submit')"
                />
            </div>
        </div>

        <ModalAlert v-if="errorMessage" :error="errorMessage" @close="clearError" />
    </form>
</template>

<script setup lang="ts">
import ModalAlert from "@/Components/Modals/ModalAlert.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import Spinner from "@/Shared/Spinner.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { type Happening, type HappeningEditPayload, useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";
import { withBaseUrl } from "@/baseUrl";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { storeToRefs } from "pinia";
import { computed, inject, onBeforeMount, reactive, ref } from "vue";

type TimeSlot = {
    time: string;
    label: string;
    is_selected?: boolean;
    is_disabled?: boolean;
};

type FormUser = {
    id: number | string;
    name: string;
};

type HappeningFormPayload = HappeningEditPayload;

const props = withDefaults(
    defineProps<{
        happening?: HappeningFormPayload;
    }>(),
    {
        happening: () => ({
            resource: {},
            start: "",
            end: "",
            label: {},
        }),
    },
);

// ------------------------------------------------
// Emits
// ------------------------------------------------
defineEmits<{
    (event: "update-happening", payload: HappeningFormPayload): void;
    (event: "submit"): void;
}>();

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();
const happeningStore = useHappeningStore();
const modal = useModal();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const route = inject<ZiggyRouteFn>("ziggyRoute")!;
const happening = reactive<HappeningFormPayload>({
    ...props.happening,
    resource: props.happening.resource ?? {},
    label:
        typeof props.happening.label === "object" &&
        props.happening.label !== null &&
        !Array.isArray(props.happening.label)
            ? { ...props.happening.label }
            : {},
});

const error = storeToRefs(happeningStore).error;
const errorMessage = computed(() => error.value?.data?.message);

const institutionSlug = appStore.institution?.slug ?? "";
const resourceGroupSlug = appStore.resourceGroup?.slug ?? "";
const isLabelEnabled = Number(appStore.settings?.resource_group?.["is_label_enabled"] ?? 0) === 1;

const isInitial = ref(true);
const isLoading = ref(false);

const start_time_slots = ref<TimeSlot[]>([]);
const end_time_slots = ref<TimeSlot[]>([]);
const start_time_slot_selected = ref(typeof happening.start === "string" ? happening.start : "");
const end_time_slot_selected = ref(typeof happening.end === "string" ? happening.end : "");

const formUsers = ref<FormUser[]>([]);
const isAdminCreateMode = computed(() => authStore.isAdmin && !happening.id);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getTimeSlotValues = async (
    resource_id: number | string | undefined,
    start: Happening["start"],
    end: Happening["end"],
    event: Event | null,
) => {
    if (!resource_id) {
        return;
    }

    if (!isInitial.value && event === null) {
        return;
    }

    isLoading.value = true;

    try {
        const url = route("resource.time_slots", {
            institution_slug: institutionSlug,
            resource_group_slug: resourceGroupSlug,
            id: resource_id,
        });

        const response = await axios.post<{ start: TimeSlot[]; end: TimeSlot[] }>(url, {
            happening_id: happening?.id,
            start,
            end,
            event,
        });

        start_time_slots.value = response.data.start ?? [];
        start_time_slot_selected.value =
            start_time_slots.value.find((time_slot) => time_slot.is_selected)?.time ?? start_time_slot_selected.value;
        // The server can correct the selection (e.g. it was no longer available);
        // keep the emitted happening in sync with that authoritative choice.
        happening.start = start_time_slot_selected.value;

        end_time_slots.value = response.data.end ?? [];
        end_time_slot_selected.value =
            end_time_slots.value.find((time_slot) => time_slot.is_selected)?.time ?? end_time_slot_selected.value;
        happening.end = end_time_slot_selected.value;

        isLoading.value = false;
        isInitial.value = false;
    } catch (error) {
        console.log(error);

        modal.close();
        void authStore.check();
    }
};

const initTimeSlots = () => {
    if (happening.resource.id) {
        void getTimeSlotValues(happening.resource.id, happening.start, happening.end, null);
    }
};

const syncTimeSlotValues = (event: Event, start_selected: string, end_selected: string) => {
    void getTimeSlotValues(happening.resource.id, start_selected, end_selected, event);

    happening.start = start_selected;
    happening.end = end_selected;
};

const can = authStore.can;

const fetchFormUsers = async () => {
    try {
        const response = await axios.get<FormUser[]>(withBaseUrl("/api/admin/user/users"));
        formUsers.value = response.data;
    } catch {
        // ignore — selector stays empty
    }
};

const clearError = () => {
    happeningStore.error = null;
};

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    initTimeSlots();
    if (isAdminCreateMode.value) {
        void fetchFormUsers();
    }
});
</script>
