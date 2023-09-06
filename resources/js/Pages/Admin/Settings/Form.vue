<template>
    <PageHead title="Admin Settings Form" page-type="admin" />

    <h1 class="text-3xl">Setting Form</h1>

    {{ form.id }}

    <form class="max-w-md mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Key -->
        <div class="mb-6">
            <label for="name" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase"> Key </label>
            <input
                id="key"
                v-model="form.key"
                type="text"
                name="key"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.key"></FormValidationError>
        </div>

        <!-- Input: Value -->
        <div class="mb-6">
            <label for="email" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Value
            </label>
            <input v-model="form.value"
                   type="text"
                   name="value"
                   id="value"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
            >
            <FormValidationError :message="form.errors.value"></FormValidationError>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="isProcessing">
                Submit
            </button>
        </div>

    </form>
</template>
<script setup>
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import FormValidationError from "../../../Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

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
let form = useForm({
    id: $page.props.id ?? '',
    key: $page.props.key ?? '',
    value: $page.props.value ?? '',
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = () => {
    isProcessing.value = true;
    if (form.id) {
        form.post('/admin/setting/update');
    }
    isProcessing.value = false;
}
</script>
