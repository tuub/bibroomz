<template>
    <PageHead
        :title="$t('admin.closings.form.title', { type: closable_type, title: translate(closable.title) })"
        page-type="admin"
    />
    <BodyHead
        :title="$t('admin.closings.form.title', { type: closable_type, title: translate(closable.title) })"
        :description="$t('admin.closings.form.description')"
    />

    <form class="max-w mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="start_date" field-key="admin.closings.form.fields.start_date"></FormLabel>
                <input
                    id="start_date"
                    v-model="form.start_date"
                    type="text"
                    name="start_date"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.closings.form.fields.start_date.placeholder')"
                />
                <FormValidationError :message="form.errors.start_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="start_time" field-key="admin.closings.form.fields.start_time"></FormLabel>
                <input
                    id="start_time"
                    v-model="form.start_time"
                    type="text"
                    name="start_time"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.closings.form.fields.start_time.placeholder')"
                />
                <FormValidationError :message="form.errors.start_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: End Date & End Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="end_date" field-key="admin.closings.form.fields.end_date"></FormLabel>
                <input
                    id="end_date"
                    v-model="form.end_date"
                    type="text"
                    name="end_date"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.closings.form.fields.end_date.placeholder')"
                />
                <FormValidationError :message="form.errors.end_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="end_time" field-key="admin.closings.form.fields.end_time"></FormLabel>
                <input
                    id="end_time"
                    v-model="form.end_time"
                    type="text"
                    name="end_time"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.closings.form.fields.end_time.placeholder')"
                />
                <FormValidationError :message="form.errors.end_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: Description -->
        <TranslatableFormField
            field="description"
            field-key="admin.closings.form.fields.description"
            :languages="languages"
            :errors="form.errors"
        >
            <template #default="{ language }">
                <textarea
                    :id="`description-${language}`"
                    v-model="form.description[language]"
                    name="description"
                    rows="4"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.closings.form.fields.description.placeholder')"
                ></textarea>
            </template>
        </TranslatableFormField>

        <FormAction
            :form="form"
            model="closing"
            cancel-route="admin.closing.index"
            :cancel-route-params="{ closable_id: closable.id, closable_type: closable_type }"
        />
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormField from "@/Components/Admin/TranslatableFormField.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    closing: {
        type: Object,
        default: () => ({}),
    },
    closable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    closable_type: {
        type: String,
        default: "",
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;

const form = useForm({
    id: props.closing.id ?? "",
    start_date: props.closing.start_date ?? "",
    start_time: props.closing.start_time ?? "",
    end_date: props.closing.end_date ?? "",
    end_time: props.closing.end_time ?? "",
    description: props.closing.description ?? {},
    closable_id: props.closable.id,
    closable_type: props.closable_type,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    if (form.id) {
        form.post("/admin/closing/update");
    } else {
        form.post("/admin/closing/store");
    }
};
</script>
