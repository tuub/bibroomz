<template>
    <PageHead title="Admin Closings Form" page_type="admin" />

    <h1 class="text-3xl">Closing Form for {{ closable_type }} "{{ closable.title }}"</h1>

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">
        <!-- Input: Start / End -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <label for="start" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Start
                </label>
                <input v-model="form.start"
                       type="text"
                       id="start"
                       name="start"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.start"></FormValidationError>
            </div>
            <div>
                <label for="end" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    End
                </label>
                <input v-model="form.end"
                       type="text"
                       id="end"
                       name="end"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.end"></FormValidationError>
            </div>
        </div>

        <!-- Input: Description -->
        <div class="mb-6">
            <label for="location" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Description
            </label>
            <input v-model="form.description"
                   type="text"
                   name="description"
                   id="description"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder="Optional description"
            >
            <FormValidationError :message="form.errors.description"></FormValidationError>
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
import PageHead from "@/Shared/PageHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    closing: Object,
    closable: Object,
    closable_type: String,
    errors: Object,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
let processing = ref(false);
let $page = usePage()
let form = useForm({
    id: $page.props.closing.id ?? '',
    start: $page.props.closing.start ?? '',
    end: $page.props.closing.end ?? '',
    description: $page.props.closing.description ?? '',
    closable_id: $page.props.closable.id,
    closable_type: $page.props.closable_type,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = () => {
    processing.value = true;
    if (form.id) {
        form.post('/admin/closing/update');
    } else {
        form.post('/admin/closing/store');
    }
}
</script>
