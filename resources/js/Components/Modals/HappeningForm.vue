<template>
    <div class="mt-4 space-y-3">
        <div class="grid md:grid-cols-2 md:gap-2">
            {{ happening }}
            <div class="mb-6">
                <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Start
                </label>
                <select v-model="timeSlots.start.selected"
                        @change="syncTimeSlotValues($event, timeSlots.start.selected, timeSlots.end.selected)"
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
                <select v-model="timeSlots.end.selected"
                        @change="syncTimeSlotValues($event, timeSlots.start.selected, timeSlots.end.selected)"
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
        <div class="relative z-0 w-full mb-6 group">
            <HappeningFormInput
                v-model:input="happening.confirmer"
                type="text"
                name="confirmer"
                id="confirmer"
                required
            />
            <label for="confirmer"
                   class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                Bestätigung durch:
            </label>
        </div>
    </div>

    <div v-if="validationErrors">{{ validationErrors }}</div>
</template>

<script setup>
import HappeningFormInput from "./HappeningFormInput.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import { storeToRefs } from "pinia";
import {onMounted, reactive, ref, computed} from "vue";

const props = defineProps({
    happening: Object,
});

let isInitial = ref(true);
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
