<template>
    <PageHead :title="$t('admin.resources.form.title')" page_type="admin" />
    <BodyHead :title="$t('admin.resources.form.title')"
              :description="$t('admin.resources.form.description')" />

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Select: Institution -->
        <div class="mb-6">
            <FormLabel field="institution_id" field-key="admin.resources.form.fields.institution"></FormLabel>
            <select v-model="form.institution_id"
                    id="institution_id"
                    name="institution_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">
                    {{ $t('admin.general.form.choose') }}
                </option>

                <option v-for="institution in institutions" :value="institution.id" :key="institution.id">
                    {{ institution.title }}
                </option>
            </select>
            <FormValidationError v-if="form.errors.institution_id" :message="form.errors.institution_id"></FormValidationError>
        </div>

        <!-- Input: Title -->
        <div class="mb-6">
            <FormLabel field="title" field-key="admin.resources.form.fields.title"></FormLabel>
            <input v-model="form.title"
                   type="text"
                   id="title"
                   name="title"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   :placeholder="$t('admin.resources.form.fields.title.placeholder')">
            <FormValidationError v-if="form.errors.title" :message="form.errors.title"></FormValidationError>
        </div>

        <!-- Input: Location -->
        <div class="mb-6">
            <FormLabel field="location" field-key="admin.resources.form.fields.location"></FormLabel>
            <input v-model="form.location"
                   type="text"
                   name="location"
                   id="location"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   :placeholder="$t('admin.resources.form.fields.location.placeholder')">
            <FormValidationError v-if="form.errors.location" :message="form.errors.location"></FormValidationError>
        </div>

        <!-- Input: Location URI -->
        <div class="mb-6">
            <FormLabel field="location_uri" field-key="admin.resources.form.fields.location_uri"></FormLabel>
            <input v-model="form.location_uri"
                   type="text"
                   name="location_uri"
                   id="location_uri"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   :placeholder="$t('admin.resources.form.fields.location_uri.placeholder')">
            <FormValidationError v-if="form.errors.location_uri" :message="form.errors.location_uri"></FormValidationError>
        </div>

        <!-- Textarea: Description -->
        <div class="mb-6">
            <FormLabel field="description" field-key="admin.resources.form.fields.description"></FormLabel>
            <textarea v-model="form.description"
                      id="description"
                      name="description"
                      rows="4"
                      class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                      :placeholder="$t('admin.resources.form.fields.description.placeholder')">
            </textarea>
            <FormValidationError v-if="form.errors.description" :message="form.errors.description"></FormValidationError>
        </div>

        <!-- Input: Capacity -->
        <div class="mb-6">
            <FormLabel field="capacity" field-key="admin.resources.form.fields.capacity"></FormLabel>
            <div class="mb-6 flex flex-row">
                <div class="w-10/12">
                    <input v-model="form.capacity"
                           type="range"
                           id="capacity"
                           name="capacity"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
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
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_active"
                       type="checkbox"
                       class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t('admin.resources.form.fields.is_active.label') }}
                </span>
            </label>
            <FormValidationError v-if="form.errors.is_active" :message="form.errors.is_active"></FormValidationError>
        </div>

        <!-- Checkbox: Is verification required -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_verification_required"
                       type="checkbox"
                       class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t('admin.resources.form.fields.is_verification_required.label') }}
                </span>
            </label>
            <FormValidationError v-if="form.errors.is_verification_required" :message="form.errors.is_verification_required"></FormValidationError>
        </div>

        <business-hour-field v-for="(time_slot, index) in form.business_hours"
                         :time_slot="time_slot"
                         :key="time_slot.id"
                         :index="index"
                         :is_last="index === form.business_hours.length - 1"
                         :is_new="isNaN(time_slot.id) === false"
                         :days_of_week="days_of_week"
                         @updateWeekDays="updateWeekDays"
                         @removeBusinessHourField="removeBusinessHourField">
        </business-hour-field>
        <FormValidationError v-if="form.errors.business_hours" :message="form.errors.business_hours"></FormValidationError>
        <div class="flex flex-wrap -mx-2 mb-4 mt-6">
            <div class="w-full px-3 text-center">
                <a href="#"
                      @click="addBusinessHourField"
                      class="p-3 bg-green-600 text-white my-13">
                    {{ $t('admin.resources.form.actions.add_business_hours') }}
                </a>
            </div>
        </div>

        <div class="mb-6" id="submitButton">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="isProcessing">
                {{ $t('admin.resources.form.actions.submit') }}
            </button>
        </div>

    </form>
</template>
<script setup>
import {onMounted, ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import BusinessHourField from "../../../Components/Admin/BusinessHourField.vue";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    errors: Object,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
let isProcessing = ref(false);
let $page = usePage()
let resource = $page.props.resource
let days_of_week = $page.props.week_days

// FIXME: this is cool but not cool. We should control the data coming from the backend in another way.
// We should find a way to define the data coming from a relation, in this case belongsToMany relation week_days.
let business_hours = []
if (resource?.business_hours) {
    for (let business_hour of resource.business_hours) {
        let week_days = business_hour.week_days.map(week_day => week_day.id).sort();
        business_hours.push({...business_hour, week_days: week_days});
    }
}

let form = useForm({
    id: resource?.id ?? '',
    institution_id: resource?.institution_id ?? '',
    title: resource?.title ?? '',
    location: resource?.location ?? '',
    location_uri: resource?.location_uri ?? '',
    description: resource?.description ?? '',
    capacity: resource?.capacity ?? '0',
    is_active: resource?.is_active ?? false,
    is_verification_required: resource?.is_verification_required ?? true,
    business_hours: business_hours ?? [],
});

let institutions = ref({})

let businessHourTemplate = {
    id: 0,
    resource_id: form.id,
    start: undefined,
    end: undefined,
    week_days: [],
}

// ------------------------------------------------
// Methods
// ------------------------------------------------
const addBusinessHourField = (e) => {
    e.preventDefault()
    // Here we're using Object.assign to prevent reactivity by reference!
    // See: https://stackoverflow.com/a/54079074/6948765
    let business_hour_field = Object.assign({}, businessHourTemplate)
    business_hour_field.id = generateUid()
    form.business_hours.push(business_hour_field)

    document.getElementById('submitButton').scrollIntoView();
}

const removeBusinessHourField = () => {
    form.business_hours.splice(-1)
}

const updateWeekDays = (e) => {
    let currentTimeSlot = form.business_hours.find(business_hour => business_hour.id === e.id);
    currentTimeSlot['week_days'] = days_of_week
        .filter(el => e.checkedWeekDays.includes(el.id))
        .map(el => el.id)
}

const generateUid = () => {
    return Date.now().toString();
}

let submitForm = () => {
    isProcessing.value = true;
    if (form.id) {
        form.post('/admin/resource/update');
    } else {
        form.post('/admin/resource/store');
    }
    isProcessing.value = false;
}

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    axios.get('/admin/form/institutions').then((response) => {
        institutions.value = response.data
    })
})
</script>
