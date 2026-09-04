<template>
    <form class="space-y-3">
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <FormLabel field="start" field-key="modal.form.fields.start" as-labelledby></FormLabel>
                <Select
                    v-model="start_time_slot_selected"
                    input-id="start"
                    aria-labelledby="start-label"
                    name="start"
                    :options="start_time_slots"
                    option-label="label"
                    option-value="time"
                    option-disabled="is_disabled"
                    :disabled="isLoading"
                    :loading="isLoading"
                    class="w-full"
                    @change="updateStartTimeSlot"
                />
            </div>

            <div>
                <FormLabel field="end" field-key="modal.form.fields.end" as-labelledby></FormLabel>
                <Select
                    v-model="end_time_slot_selected"
                    input-id="end"
                    aria-labelledby="end-label"
                    name="end"
                    :options="end_time_slots"
                    option-label="label"
                    option-value="time"
                    option-disabled="is_disabled"
                    :disabled="isLoading"
                    :loading="isLoading"
                    class="w-full"
                    @change="updateEndTimeSlot"
                />
            </div>
        </div>

        <div v-if="isAdminCreateMode">
            <FormLabel field="user_id_01" field-key="modal.form.fields.user_id_01" as-labelledby></FormLabel>
            <Select
                v-model="happening.user_id_01"
                input-id="user_id_01"
                aria-labelledby="user_id_01-label"
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
                class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
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
                    class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
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

type TimeSlotSelectChangeEvent = {
    value: string;
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
const emit = defineEmits<{
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
    isUserChange: boolean,
) => {
    if (!resource_id) {
        return;
    }

    if (!isInitial.value && !isUserChange) {
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
        void getTimeSlotValues(happening.resource.id, happening.start, happening.end, false);
    }
};

const syncTimeSlotValues = (start_selected: string, end_selected: string) => {
    void getTimeSlotValues(happening.resource.id, start_selected, end_selected, true);

    happening.start = start_selected;
    happening.end = end_selected;
};

const updateStartTimeSlot = (event: TimeSlotSelectChangeEvent) => {
    syncTimeSlotValues(event.value, end_time_slot_selected.value);
    emit("update-happening", happening);
};

const updateEndTimeSlot = (event: TimeSlotSelectChangeEvent) => {
    syncTimeSlotValues(start_time_slot_selected.value, event.value);
    emit("update-happening", happening);
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
