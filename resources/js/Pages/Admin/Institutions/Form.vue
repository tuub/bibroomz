<template>
    <Head title="Resource Form" />
    <h1 class="text-3xl">Institution Form</h1>

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

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

        <!-- Input: Short title -->
        <div class="mb-6">
            <label for="title" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Short Title
            </label>
            <input v-model="form.short_title"
                   type="text"
                   id="short_title"
                   name="short_title"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   required
            >
            <FormValidationError :message="form.errors.short_title"></FormValidationError>
        </div>

        <!-- Input: Slug -->
        <div class="mb-6">
            <label for="title" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Slug
            </label>
            <input v-model="form.slug"
                   type="text"
                   id="slug"
                   name="slug"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   required
            >
            <FormValidationError :message="form.errors.slug"></FormValidationError>
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

        <!-- Input: Home URI -->
        <div class="mb-6">
            <label for="home_uri" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Home URI
            </label>
            <input v-model="form.home_uri"
                   type="text"
                   name="home_uri"
                   id="home_uri"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
            >
            <FormValidationError :message="form.errors.home_uri"></FormValidationError>
        </div>

        <!-- Input: Logo URI -->
        <div class="mb-6">
            <label for="logo_uri" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Logo URI
            </label>
            <input v-model="form.logo_uri"
                   type="text"
                   name="logo_uri"
                   id="logo_uri"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
            >
            <FormValidationError :message="form.errors.logo_uri"></FormValidationError>
        </div>

        <!-- Input: Teaser URI -->
        <div class="mb-6">
            <label for="teaser_uri" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Teaser URI
            </label>
            <input v-model="form.teaser_uri"
                   type="text"
                   name="teaser_uri"
                   id="teaser_uri"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
            >
            <FormValidationError :message="form.errors.teaser_uri"></FormValidationError>
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
            <label for="title" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Closings
            </label>
            <div v-for="closing in closings">
                {{ formatDateTime(closing.start) }} - {{ formatDateTime(closing.end) }}
            </div>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>

    </form>
</template>
<script setup>
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import FormValidationError from "../../../Shared/FormValidationError.vue";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    errors: Object,
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc)

// ------------------------------------------------
// Variables
// ------------------------------------------------
let processing = ref(false);
let $page = usePage()
let closings = $page.props.closings
let form = useForm({
    id: $page.props.id ?? '',
    title: $page.props.title ?? '',
    short_title: $page.props.short_title ?? '',
    slug: $page.props.slug ?? '',
    location: $page.props.location ?? '',
    home_uri: $page.props.home_uri ?? '',
    logo_uri: $page.props.logo_uri ?? '',
    teaser_uri: $page.props.teaser_uri ?? '',
    is_active: $page.props.is_active === 1,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatDateTime = ((dateTime) => {
    return dayjs.utc(dateTime).format('DD.MM.YYYY HH:mm');
})

let submitForm = () => {
    processing.value = true;
    if (form.id) {
        form.post('/admin/institution/update');
    } else {
        form.post('/admin/institution/store');
    }
}
</script>
