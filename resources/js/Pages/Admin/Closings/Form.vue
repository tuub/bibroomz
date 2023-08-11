<template>
    <PageHead :title="$t('admin.closings.form.title',{type: closable_type, title: closable.title})"
              page_type="admin" />
    <BodyHead :title="$t('admin.closings.form.title', {type: closable_type, title: closable.title})"
              :description="$t('admin.closings.form.description')" />

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="start_date" field-key="admin.closings.form.fields.start_date"></FormLabel>
                <input v-model="form.start_date"
                       type="text"
                       id="start_date"
                       name="start_date"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.closings.form.fields.start_date.placeholder')">
                <FormValidationError :message="form.errors.start_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="start_time" field-key="admin.closings.form.fields.start_time"></FormLabel>
                <input v-model="form.start_time"
                       type="text"
                       id="start_time"
                       name="start_time"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.closings.form.fields.start_time.placeholder')">
                <FormValidationError :message="form.errors.start_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: End Date & End Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="end_date" field-key="admin.closings.form.fields.end_date"></FormLabel>
                <input v-model="form.end_date"
                       type="text"
                       id="end_date"
                       name="end_date"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.closings.form.fields.end_date.placeholder')"
                >
                <FormValidationError :message="form.errors.end_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="end_time" field-key="admin.closings.form.fields.end_time"></FormLabel>
                <input v-model="form.end_time"
                       type="text"
                       id="end_time"
                       name="end_time"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.closings.form.fields.end_time.placeholder')"
                >
                <FormValidationError :message="form.errors.end_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: Description -->
        <div class="mb-6">
            <FormLabel field="description" field-key="admin.closings.form.fields.description"></FormLabel>
            <input v-model="form.description"
                   type="text"
                   name="description"
                   id="description"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   :placeholder="$t('admin.closings.form.fields.description.placeholder')"
            >
            <FormValidationError :message="form.errors.description"></FormValidationError>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                {{ $t('admin.closings.form.actions.submit') }}
            </button>
        </div>

    </form>
</template>
<script setup>
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

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
    start_date: $page.props.closing.start_date ?? '',
    start_time: $page.props.closing.start_time ?? '',
    end_date: $page.props.closing.end_date ?? '',
    end_time: $page.props.closing.end_time ?? '',
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
