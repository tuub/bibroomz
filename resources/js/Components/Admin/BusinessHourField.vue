<template>
    <div class="flex flex-wrap -mx-3">
        <div class="w-full mx-3 font-bold text-black">
            {{ $t("admin.resources.form.fields.business_hours.label", { index: (index + 1).toString() }) }}
            <a v-if="!isOnly" href="#" class="p5" @click.prevent="removeBusinessHourField">
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
                    @change="updateBusinessHourField"
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
                @change="updateBusinessHourField"
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
                @change="updateBusinessHourField"
            />
            <FormValidationError v-if="errors?.end" :message="errors.end"></FormValidationError>
        </div>
        <div class="grid gap-6 mb-6 md:grid-cols-2 w-full px-3 my-3">
            <div>
                <FormLabel field="start_date" field-key="admin.resources.form.fields.business_hours.subfields.start_date"></FormLabel>
                <input
                    id="start_date"
                    v-model="startDate"
                    type="text"
                    name="start_date"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.resources.form.fields.business_hours.subfields.start_date.placeholder')"
                    @change="updateBusinessHourField"
                />
                <FormValidationError v-if="errors?.startDate" :message="errors.startDate"></FormValidationError>
            </div>
            <div>
                <FormLabel field="end_date" field-key="admin.resources.form.fields.business_hours.subfields.end_date"></FormLabel>
                <input
                    id="end_date"
                    v-model="endDate"
                    type="text"
                    name="end_date"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.resources.form.fields.business_hours.subfields.end_date.placeholder')"
                    @change="updateBusinessHourField"
                />
                <FormValidationError v-if="errors?.endDate" :message="errors.endDate"></FormValidationError>
            </div>
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
    daysOfWeek: {
        type: Array,
        required: true,
    },
    isOnly: {
        type: Boolean,
        default: false,
    },
    startDate: {
        type: Date,
        default: null,
    },
    endDate: {
        type: Date,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(["remove-business-hour-field", "update-business-hour-field"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const start = ref(props.timeSlot.start);
const end = ref(props.timeSlot.end);

const startDate = ref(props.timeSlot.start_date);
const endDate = ref(props.timeSlot.end_date);

const checkedWeekDays = ref(props.timeSlot.week_days ?? []);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const removeBusinessHourField = () => {
    emit("remove-business-hour-field", {
        time_slot: props.timeSlot,
    });
};

const updateBusinessHourField = () => {
    emit("update-business-hour-field", {
        id: props.timeSlot.id,
        start,
        end,
        startDate,
        endDate,
        checkedWeekDays: checkedWeekDays.value.sort(),
    });
};
</script>
