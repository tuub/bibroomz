<template>
    <div class="flex flex-wrap -mx-3">
        <div class="w-full mx-3 font-bold text-black">
            {{ $t("admin.resources.form.fields.business_hours.label", { index: (index + 1).toString() }) }}
            <a v-if="isLast" href="#" class="p5" @click.prevent="removeBusinessHourField">
                <i class="ri-delete-bin-line"></i>
            </a>
        </div>
        <div class="w-full px-3 mb-6 md:mb-0">
            <FormLabel
                field="weekDays"
                field-key="admin.resources.form.fields.business_hours.subfields.week_days"
            ></FormLabel>
            <span v-for="dayOfWeek in daysOfWeek" :key="dayOfWeek.id" class="mr-2">
                <input
                    v-model="checkedWeekDays"
                    name="weekDays"
                    type="checkbox"
                    :value="dayOfWeek.id"
                    @change="updateWeekDays"
                />
                {{ $t("admin.general.week_days." + dayOfWeek.key + ".short_label") }}
            </span>
            <FormValidationError v-if="errors?.weekDays" :message="errors.weekDays"></FormValidationError>
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <FormLabel
                :field="`businessHourStart-${index}`"
                field-key="admin.resources.form.fields.business_hours.subfields.start"
            ></FormLabel>
            <input
                :id="`businessHourStart-${index}`"
                v-model="start"
                name="businessHourStart"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                type="text"
                :placeholder="$t('admin.resources.form.fields.business_hours.subfields.start.placeholder')"
                @change="updateTimeSlot"
            />
            <FormValidationError v-if="errors?.start" :message="errors.start"></FormValidationError>
        </div>
        <div class="w-full md:w-2/4 px-3 my-3">
            <FormLabel
                :field="`businessHourEnd-${index}`"
                field-key="admin.resources.form.fields.business_hours.subfields.end"
            ></FormLabel>
            <input
                :id="`businessHourEnd-${index}`"
                v-model="end"
                name="businessHourEnd"
                class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white"
                type="text"
                :placeholder="$t('admin.resources.form.fields.business_hours.subfields.end.placeholder')"
                @change="updateTimeSlot"
            />
            <FormValidationError v-if="errors?.end" :message="errors.end"></FormValidationError>
        </div>
    </div>
</template>

<script setup>
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    timeSlot: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    isLast: {
        type: Boolean,
        required: true,
    },
    isNew: {
        type: Boolean,
        required: true,
    },
    daysOfWeek: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(["remove-business-hour-field", "update-week-days", "update-time-slot"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const start = ref(props.timeSlot.start);
const end = ref(props.timeSlot.end);

const checkedWeekDays = ref(props.timeSlot.week_days ?? []);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const removeBusinessHourField = () => {
    emit("remove-business-hour-field", {
        time_slot: props.timeSlot,
    });
};

const updateWeekDays = () => {
    emit("update-week-days", {
        id: props.timeSlot.id,
        checkedWeekDays: checkedWeekDays.value.sort(),
    });
};

const updateTimeSlot = () => {
    emit("update-time-slot", {
        id: props.timeSlot.id,
        start,
        end,
    });
};
</script>
