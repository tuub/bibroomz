<template>
    <div class="flex flex-wrap -mx-3 mb-2 mt-4">
        <div class="w-full mx-3 font-bold text-black">
            {{ $t('admin.resources.form.fields.business_hours.label', {index: (index+1).toString()}) }}
            <a v-if="is_last" href="#" class="p5" @click="removeBusinessHourField">
                <i class="ri-delete-bin-line"></i>
            </a>
        </div>
        <div class="w-full px-3 mb-6 md:mb-0">
            <FormLabel field="week_days" field-key="admin.resources.form.fields.business_hours.subfields.week_days"></FormLabel>
            <span v-for="day_of_week in days_of_week" class="mr-2">
                <input type="checkbox" :value="day_of_week.id" name="week_days[]" v-model="checkedWeekDays" @change="updateWeekDays"/>
                {{ $t('admin.general.week_days.' + day_of_week.key + '.short_label') }}
            </span>
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <FormLabel field="business_hour_start" field-key="admin.resources.form.fields.business_hours.subfields.start"></FormLabel>
            <input class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                   v-model="time_slot.start"
                   type="text"
                   :placeholder="$t('admin.resources.form.fields.business_hours.subfields.start.placeholder')">
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <FormLabel field="business_hour_end" field-key="admin.resources.form.fields.business_hours.subfields.end"></FormLabel>
            <input class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                   v-model="time_slot.end"
                   type="text"
                   :placeholder="$t('admin.resources.form.fields.business_hours.subfields.end.placeholder')">
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    time_slot: Object,
    index: Number,
    is_last: Boolean,
    is_new: Boolean,
    days_of_week: Array,
})

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(['removeBusinessHourField', 'updateWeekDays'])

// ------------------------------------------------
// Variables
// ------------------------------------------------
let checkedWeekDays = ref([])

// ------------------------------------------------
// Methods
// ------------------------------------------------
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

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    checkedWeekDays.value = getWeekDays()
})
</script>
