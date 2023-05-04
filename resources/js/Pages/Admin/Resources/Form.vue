<template>
    <Head title="Resource Form" />
    <h1 class="text-3xl">Resource Form</h1>

    {{ closings }}

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Select: Institution -->
        <div class="mb-6">
            <label for="institution_id" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Institution
            </label>
            <select v-model="form.institution_id"
                    id="institution_id"
                    name="institution_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">Choose</option>

                <option v-for="institution in institutions" :value="institution.id" :key="institution.id">
                    {{ institution.title }}
                </option>
            </select>
            <FormValidationError :message="form.errors.institution_id"></FormValidationError>
        </div>

        <!-- Input: Title -->
        <div class="mb-6">
            <label for="title" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Title
            </label>
            <input v-model="form.title"
                   type="text"
                   id="title"
                   name="title"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   required
            >
            <FormValidationError :message="form.errors.title"></FormValidationError>
        </div>

        <!-- Input: Location -->
        <div class="mb-6">
            <label for="location" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Location
            </label>
            <input v-model="form.location"
                   type="text"
                   name="location"
                   id="location"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   required
            >
            <FormValidationError :message="form.errors.location"></FormValidationError>
        </div>

        <!-- Textarea: Description -->
        <div class="mb-6">
            <label for="description" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Description
            </label>
            <textarea v-model="form.description"
                      id="description"
                      name="description"
                      rows="4"
                      class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                      placeholder="Write your thoughts here..."
            >
            </textarea>
            <FormValidationError :message="form.errors.description"></FormValidationError>
        </div>

        <!-- Input: Capacity -->
        <div class="mb-6">
            <label for="capacity" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Capacity
            </label>
            <div class="mb-6 flex flex-row">
                <div class="w-10/12">
                    <input v-model="form.capacity"
                           type="range"
                           id="capacity"
                           name="capacity"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                    >
                </div>
                <div class="w-2/12 text-center">
                    <i class="ri-arrow-right-double-line"></i>
                    <span class="ml-2 font-bold">{{ form.capacity }}</span>
                </div>
            </div>
            <FormValidationError :message="form.errors.capacity"></FormValidationError>
        </div>

        <!-- Input: Opens at / Closes at -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <label for="opens_at" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Opens at
                </label>
                <input v-model="form.opens_at"
                       type="text"
                       id="opens_at"
                       name="opens_at"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.opens_at"></FormValidationError>
            </div>
            <div>
                <label for="closes_at" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Closes at
                </label>
                <input v-model="form.closes_at"
                       type="text"
                       id="closes_at"
                       name="closes_at"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                       >
                <FormValidationError :message="form.errors.closes_at"></FormValidationError>
            </div>
        </div>

        <!-- Checkbox: Is active -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_active"
                       type="checkbox"
                       class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Is active
                </span>
            </label>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>

    </form>
</template>
<script setup>
import {onMounted, reactive, ref} from "vue";
//import { router } from "@inertiajs/vue3";
import {useForm, usePage} from "@inertiajs/vue3";
import FormValidationError from "../../../Shared/FormValidationError.vue";

defineProps({
    errors: Object,
});

let processing = ref(false);
let $page = usePage()

let closings = $page.props.closings

let form = useForm({
    id: $page.props.id ?? '',
    institution_id: $page.props.institution_id ?? '',
    title: $page.props.title ?? '',
    location: $page.props.location ?? '',
    description: $page.props.description ?? '',
    capacity: $page.props.capacity ?? '0',
    opens_at: $page.props.opens_at ?? '',
    closes_at: $page.props.closes_at ?? '',
    is_active: $page.props.is_active === 1,
});

let institutions = ref({})

let submitForm = () => {
    processing.value = true;
    if (form.id) {
        form.post('/admin/resource/update');
    } else {
        form.post('/admin/resource/store');
    }
}

onMounted(() => {
    axios.get('/admin/form/institutions').then((response) => {
        institutions.value = response.data
    })
})

</script>
