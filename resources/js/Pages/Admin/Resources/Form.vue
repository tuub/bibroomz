<template>
    <PageHead :title="$t('admin.resources.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.resources.form.title')" :description="$t('admin.resources.form.description')" />

    <form class="max-w mx-auto mt-8">
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
        <div class="mb-6">
            <FormLabel field="location_uri" field-key="admin.resources.form.fields.location_uri"></FormLabel>
            <input
                id="location_uri"
                v-model="form.location_uri"
                type="text"
                name="location_uri"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.resources.form.fields.location_uri.placeholder')"
            />
            <FormValidationError
                v-if="form.errors.location_uri"
                :message="form.errors.location_uri"
            ></FormValidationError>
        </div>

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
        <div class="mb-6">
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
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="peer sr-only" />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.resources.form.fields.is_active.label") }}
                </span>
            </label>
            <FormValidationError v-if="form.errors.is_active" :message="form.errors.is_active"></FormValidationError>
        </div>

        <!-- Checkbox: Is verification required -->
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input
                    id="is_verification_required"
                    v-model="form.is_verification_required"
                    type="checkbox"
                    class="peer sr-only"
                />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.resources.form.fields.is_verification_required.label") }}
                </span>
            </label>
            <FormValidationError
                v-if="form.errors.is_verification_required"
                :message="form.errors.is_verification_required"
            ></FormValidationError>
        </div>

        <div class="mb-6">
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
    </form>
</template>
<script setup>
import BusinessHourField from "@/Components/Admin/BusinessHourField.vue";
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";

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
