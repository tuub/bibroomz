<template>
    <div class="mt-4 space-y-3">
        <div class="grid md:grid-cols-2 md:gap-2">
            <div class="mb-6">
                <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Start
                </label>
                <spinner size="small" v-if="isLoading"></spinner>
                <select v-else
                        @change="syncTimeSlotValues($event, start_time_slot_selected, end_time_slot_selected)"
                        v-model="start_time_slot_selected"
                        @input="$emit('update:input', $event.target.value)"
                        id="start"
                        name="start"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option v-for="start_time_slot in start_time_slots"
                            :value="start_time_slot.time"
                            :key="start_time_slot.time"
                            :selected="start_time_slot.is_selected"
                            :disabled="start_time_slot.is_disabled">
                        {{ start_time_slot.label }}
                    </option>
                </select>
            </div>

            <div class="mb-6">
                <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    End
                </label>
                <spinner size="small" v-if="isLoading"></spinner>
                <select v-else
                        @change="syncTimeSlotValues($event, start_time_slot_selected, end_time_slot_selected)"
                        v-model="end_time_slot_selected"
                        @input="$emit('update:input', $event.target.value)"
                        id="end"
                        name="end"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option v-for="end_time_slot in end_time_slots"
                            :value="end_time_slot.time"
                            :key="end_time_slot.time"
                            :selected="end_time_slot.is_selected"
                            :disabled="end_time_slot.is_disabled">
                        {{ end_time_slot.label }}
                    </option>
                </select>
            </div>
        </div>
        <div v-if="happening.isVerificationRequired && !isAdmin" class="mb-6">
            <label for="verifier" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Verifier
            </label>
            <input v-model="happening.verifier"
                   @input="$emit('update:input', $event.target.value)"
                   type="text"
                   id="verifier"
                   name="verifier"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   :disabled="!!happening.id"
            >
        </div>
    </div>

    <div v-if="validationErrors">{{ validationErrors }}</div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useHappeningStore } from "@/Stores/HappeningStore";
import { storeToRefs } from "pinia";
import {ref, onBeforeMount} from "vue";
import Spinner from "../../Shared/Spinner.vue";
import { useAuthStore } from "@/Stores/AuthStore";

const props = defineProps({
    happening: Object,
});

let appStore = useAppStore()
let slug = appStore.institution.slug

const emit = defineEmits(["update:input"]);

const authStore = useAuthStore();
let { isAdmin } = storeToRefs(authStore);

let isInitial = ref(true);
let isLoading = ref(false);

let start_time_slots = ref({});
let end_time_slots = ref({});
let start_time_slot_selected = ref({})
let end_time_slot_selected = ref({})

const getTimeSlotValues = (resource_id, start, end, event) => {
    if (isInitial || event !== null) {
        isLoading.value = true
        let payload = {
            resource_id: resource_id,
            start: start,
            end: end,
            event: event,
            happening_id: props.happening?.id,
        }
        axios.post('/' + slug + '/resource/' + resource_id + '/time_slots', payload).then((response) => {
            start_time_slots.value = response.data['start']
            if (response.data['start']) {
                start_time_slot_selected.value = response.data['start']?.filter(obj => {
                    return obj.is_selected === true
                })[0]?.time
            }
            end_time_slots.value = response.data['end']
            if (response.data['end']) {
                end_time_slot_selected.value = response.data['end']?.filter(obj => {
                    return obj.is_selected === true
                })[0]?.time
            }
            isLoading.value = false
        })
        isInitial.value = false;
    }
}

const initTimeSlots = () => {
    let happening = props.happening
    if (happening.resource.id) {
        getTimeSlotValues(happening.resource.id, happening.start, happening.end, null);
    }
}

const syncTimeSlotValues = ($event, start_selected, end_selected) => {
    getTimeSlotValues(props.happening.resource.id, start_selected, end_selected, $event);
    props.happening.start = start_time_slot_selected
    // FIXME: not changed!
    props.happening.end = end_time_slot_selected
}

const happeningStore = useHappeningStore();
let { validationErrors } = storeToRefs(happeningStore);

onBeforeMount(() => {
    initTimeSlots()
})

</script>
