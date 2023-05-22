<template>
    <div class="mt-4 space-y-3">
        {{ happening }}
        <div class="grid md:grid-cols-2 md:gap-2">
            <div class="mb-6">
                <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Start
                </label>
                <spinner size="small" v-if="isLoading"></spinner>
                <select v-else
                        v-model="timeSlots.start.selected"
                        @change="syncTimeSlotValues($event, timeSlots.start.selected, timeSlots.end.selected)"
                        @input="$emit('update:input', $event.target.value)"
                        id="start"
                        name="start"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option v-for="(startTimeSlot, startTimeSlotKey) in timeSlots.start.range"
                            :value="startTimeSlot"
                            :key="startTimeSlotKey">
                        {{ startTimeSlotKey }}
                    </option>
                </select>
            </div>

            <div class="mb-6">
                <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    End
                </label>
                <spinner size="small" v-if="isLoading"></spinner>
                <select v-else
                        v-model="timeSlots.end.selected"
                        @change="syncTimeSlotValues($event, timeSlots.start.selected, timeSlots.end.selected)"
                        @input="$emit('update:input', $event.target.value)"
                        id="end"
                        name="end"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option v-for="(endTimeSlot, endTimeSlotKey) in timeSlots.end.range"
                            :value="endTimeSlot"
                            :key="endTimeSlotKey">
                        {{ endTimeSlotKey }}
                    </option>
                </select>
            </div>
        </div>
        <div class="mb-6">
            <label for="confirmer" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Confirmer
            </label>
            <input v-model="happening.confirmer"
                   @input="$emit('update:input', $event.target.value)"
                   type="text"
                   id="confirmer"
                   name="confirmer"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
            >
        </div>
    </div>

    <div v-if="validationErrors">{{ validationErrors }}</div>
</template>

<script setup>
import HappeningFormInput from "./HappeningFormInput.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import { storeToRefs } from "pinia";
import {onMounted, reactive, ref, computed} from "vue";
import Spinner from "../../Shared/Spinner.vue";

const props = defineProps({
    happening: Object,
});

const emit = defineEmits(["update:input"]);

let isInitial = ref(true);
let isLoading = ref(false);
let timeSlots = ref({
    start: {
        range: {},
        selected: ''
    },
    end: {
        range: {},
        selected: ''
    },
});

const getTimeSlotValues = (resource_id, start, end, event) => {
    if (isInitial || event !== null) {
        isLoading.value = true
        let payload = {
            resource_id: resource_id,
            start: start,
            end: end,
            event: event,
        }
        axios.post('/timeslots', payload).then((response) => {
            timeSlots.value = {
                start: {
                    range: response.data['start'],
                    selected: response.data['start_selected'],
                },
                end: {
                    range: response.data['end'],
                    selected: response.data['end_selected'],
                },
            };
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

const syncTimeSlotValues = ($event, selectedStart, selectedEnd) => {
    getTimeSlotValues(props.happening.resource.id, selectedStart, selectedEnd, $event);
    props.happening.start = selectedStart
    // FIXME: not changed!
    props.happening.end = selectedEnd
}

const happeningStore = useHappeningStore();
let { validationErrors } = storeToRefs(happeningStore);

onMounted(() => {
    initTimeSlots()
})

</script>
