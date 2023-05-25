<template>
    <div class="flex flex-wrap -mx-3 mb-2 mt-4">
        <div class="w-full mx-3 font-bold text-black">
            Business Hours #{{ index+1 }}
            <a href="#"
                  v-if="is_last"
                  @click="removeBusinessHourField"
                  class="p5">
                <i class="ri-delete-bin-line"></i>
            </a>
        </div>
        <div class="w-full px-3 mb-6 md:mb-0">
            <span v-for="day_of_week in days_of_week">
                <input type="checkbox"
                       :value="day_of_week.id"
                       name="week_days[]"
                       v-model="checkedWeekDays"
                       @change="updateWeekDays"/>
                {{ day_of_week.name }}
            </span>
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                Start
            </label>
            <input class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                   v-model="time_slot.start"
                   type="text"
                   placeholder="Opening Time">
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                End
            </label>
            <input class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                   v-model="time_slot.end"
                   type="text"
                   placeholder="Closing Time">
        </div>
    </div>
</template>

<script setup>

import {onMounted, ref} from "vue";

let props = defineProps({
    time_slot: Object,
    index: Number,
    is_last: Boolean,
    is_new: Boolean,
    days_of_week: Array,
})

const emit = defineEmits(['removeBusinessHourField', 'updateWeekDays'])

let checkedWeekDays = ref([])

const removeBusinessHourField = () => {
    emit('removeBusinessHourField', {
        time_slot: props.time_slot
    })
}

const getWeekDays = () => {
    if (props.time_slot.week_days) {
        return props.time_slot.week_days;
    }
    return []
}

const updateWeekDays = () => {
    emit('updateWeekDays', {
        id: props.time_slot.id,
        checkedWeekDays: checkedWeekDays.value.sort()
    })
}

onMounted(() => {
    checkedWeekDays.value = getWeekDays()
})
</script>
