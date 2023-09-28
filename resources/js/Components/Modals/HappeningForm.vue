<template>
    <div class="mt-4 space-y-3">
        <div class="grid md:grid-cols-2 md:gap-2">
            <div class="mb-6">
                <FormLabel field="start" field-key="modal.form.fields.start"></FormLabel>
                <Spinner v-if="isLoading" size="small"></Spinner>
                <select
                    v-else
                    id="start"
                    v-model="start_time_slot_selected"
                    name="start"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    @change="syncTimeSlotValues($event, start_time_slot_selected, end_time_slot_selected)"
                    @input="$emit('update:happening', happening)"
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

            <div class="mb-6">
                <FormLabel field="end" field-key="modal.form.fields.end"></FormLabel>
                <Spinner v-if="isLoading" size="small"></Spinner>
                <select
                    v-else
                    id="end"
                    v-model="end_time_slot_selected"
                    name="end"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
        <div v-if="happening.isVerificationRequired && !can('no_verifier')" class="mb-6">
            <FormLabel field="verifier" field-key="modal.form.fields.verifier"></FormLabel>
            <input
                id="verifier"
                v-model="happening.verifier"
                type="text"
                name="verifier"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                :placeholder="$t('modal.form.fields.verifier.placeholder')"
                :disabled="!!happening.id"
                @input="$emit('update-happening', happening)"
                @keypress.enter.prevent="$emit('submit')"
            />
        </div>

        <ModalAlert v-if="errorMessage" :error="errorMessage" @close="error = null" />
    </div>
</template>

<script setup>
import ModalAlert from "@/Components/Modals/ModalAlert.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import Spinner from "@/Shared/Spinner.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";

import { storeToRefs } from "pinia";
import {computed, inject, onBeforeMount, reactive, ref} from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
defineEmits(["update-happening", "submit"]);

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
const route = inject('route');
const happening = reactive(props.happening);

const error = storeToRefs(happeningStore).error;
const errorMessage = computed(() => error.value?.data?.message);

const institutionSlug = appStore.institution.slug;
const resourceGroupSlug = appStore.resourceGroup.slug;

const isInitial = ref(true);
const isLoading = ref(false);

const start_time_slots = ref({});
const end_time_slots = ref({});
const start_time_slot_selected = ref({});
const end_time_slot_selected = ref({});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getTimeSlotValues = async (resource_id, start, end, event) => {
    if (!isInitial.value && event === null) {
        return;
    }

    isLoading.value = true;

    try {
        const url = route('resource.time_slots', {
            institution_slug: institutionSlug,
            resource_group_slug: resourceGroupSlug,
            id: resource_id
        });

        const response = await axios.post(url, {
            happening_id: happening?.id,
            start: start,
            end: end,
            event: event,
        });

        start_time_slots.value = response.data["start"];
        start_time_slot_selected.value =
            start_time_slots.value?.filter((time_slot) => time_slot.is_selected)[0]?.time ??
            start_time_slot_selected.value;

        end_time_slots.value = response.data["end"];
        end_time_slot_selected.value =
            end_time_slots.value?.filter((time_slot) => time_slot.is_selected)[0]?.time ?? end_time_slot_selected.value;

        isLoading.value = false;
        isInitial.value = false;
    } catch (error) {
        console.log(error)

        modal.close();
        authStore.check();
    }
};

const initTimeSlots = () => {
    if (happening.resource.id) {
        getTimeSlotValues(happening.resource.id, happening.start, happening.end, null);
    }
};

const syncTimeSlotValues = ($event, start_selected, end_selected) => {
    getTimeSlotValues(happening.resource.id, start_selected, end_selected, $event);

    happening.start = start_time_slot_selected;
    happening.end = end_time_slot_selected;
};

const can = authStore.can;

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    initTimeSlots();
});
</script>
