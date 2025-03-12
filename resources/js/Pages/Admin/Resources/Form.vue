<template>
    <FormLayout :title="$t('admin.resources.form.title')" :description="$t('admin.resources.form.description')">
        <!-- Input: Title -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.resources.form.fields.title"
            :placeholder="$t('admin.resources.form.fields.title.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Location -->
        <TranslatableFormInput
            v-model="form.location"
            field="location"
            field-key="admin.resources.form.fields.location"
            :placeholder="$t('admin.resources.form.fields.location.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Location URI -->
        <FormInput
            v-model="form.location_uri"
            field="location_uri"
            field-key="admin.resources.form.fields.location_uri"
            :error="form.errors.location_uri"
        />

        <!-- Textarea: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.resources.form.fields.description"
            :placeholder="$t('admin.resources.form.fields.description.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="4"
        ></TranslatableFormInput>

        <!-- Input: Capacity -->
        <div>
            <FormLabel field="capacity" field-key="admin.resources.form.fields.capacity"></FormLabel>
            <div class="mb-6 flex flex-row">
                <div class="w-10/12">
                    <input
                        id="capacity"
                        v-model="form.capacity"
                        type="range"
                        name="capacity"
                        class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700"
                    />
                </div>
                <div class="w-2/12 text-center">
                    <i class="ri-arrow-right-double-line"></i>
                    <span class="ml-2 font-bold">{{ form.capacity }}</span>
                </div>
            </div>
            <FormValidationError v-if="form.errors.capacity" :message="form.errors.capacity"></FormValidationError>
        </div>

        <!-- Checkbox: Is active -->
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_active" input-id="is_active" />
            <FormLabel field="is_active" field-key="admin.resources.form.fields.is_active" class="inline-block" />
            <FormValidationError :message="form.errors.is_active"></FormValidationError>
        </div>

        <!-- Checkbox: Is verification required -->
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_verification_required" input-id="is_verification_required" />
            <FormLabel
                field="is_verification_required"
                field-key="admin.resources.form.fields.is_verification_required"
                class="inline-block"
            />
            <FormValidationError :message="form.errors.is_verification_required"></FormValidationError>
        </div>

        <div>
            <BusinessHourField
                v-for="(timeSlot, index) in form.business_hours"
                :key="timeSlot.id"
                :time-slot="timeSlot"
                :index="index"
                :is-only="form.business_hours.length === 1"
                :days-of-week="weekDays"
                :errors="getBusinessHourErrors(index)"
                @remove-business-hour-field="removeBusinessHourField"
                @update-business-hour-field="updateBusinessHourField"
            ></BusinessHourField>

            <div class="mb-4 mt-4 flex flex-wrap">
                <div class="w-full text-center">
                    <a href="#" class="my-13 bg-green-600 p-3 text-white" @click.prevent="addBusinessHourField">
                        {{ $t("admin.resources.form.actions.add_business_hours") }}
                    </a>
                </div>
            </div>

            <FormValidationError
                v-if="form.errors.business_hours"
                :message="form.errors.business_hours"
            ></FormValidationError>
        </div>

        <FormAction
            :form="form"
            model="resource"
            cancel-route="admin.resource.index"
            :cancel-route-params="{ resource_group_id: resourceGroup.id }"
        />
    </FormLayout>
</template>
<script setup>
import BusinessHourField from "@/Components/Admin/BusinessHourField.vue";
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

import { useForm } from "@inertiajs/vue3";
import ToggleSwitch from "primevue/toggleswitch";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    resource: {
        type: Object,
        default: () => ({}),
    },
    weekDays: {
        type: Array,
        default: () => [],
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const form = useForm({
    id: props.resource?.id ?? "",
    title: props.resource?.title ?? {},
    location: props.resource?.location ?? {},
    location_uri: props.resource?.location_uri ?? "",
    description: props.resource?.description ?? {},
    capacity: props.resource?.capacity ?? "0",
    is_active: props.resource?.is_active ?? false,
    is_verification_required: props.resource?.is_verification_required ?? true,
    business_hours: props.resource?.business_hours ?? [],
    resource_group_id: props.resourceGroup.id,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const addBusinessHourField = () => {
    const businessHourField = {
        id: generateUid(),
        resource_id: form.id,
    };

    form.business_hours.push(businessHourField);
};

const removeBusinessHourField = () => {
    form.business_hours.splice(-1);
};

const updateBusinessHourField = ({ id, start, end, startDate, endDate, checkedWeekDays }) => {
    const currentTimeSlot = form.business_hours.find((business_hour) => business_hour.id === id);

    currentTimeSlot.start = start;
    currentTimeSlot.end = end;

    currentTimeSlot.start_date = startDate;
    currentTimeSlot.end_date = endDate;

    currentTimeSlot.week_days = props.weekDays.filter((el) => checkedWeekDays.includes(el.id)).map((el) => el.id);
};

const generateUid = () => {
    return Date.now().toString();
};

const getBusinessHourErrors = (index) => {
    return {
        start: form.errors[`business_hours.${index}.start`],
        end: form.errors[`business_hours.${index}.end`],
        startDate: form.errors[`business_hours.${index}.start_date`],
        endDate: form.errors[`business_hours.${index}.end_date`],
        weekDays: form.errors[`business_hours.${index}.week_days`],
    };
};
</script>
