<template>
    <PageHead
        :title="$t('admin.closings.form.title', { type: closable_type, title: closable.title })"
        page_type="admin"
    />
    <BodyHead
        :title="$t('admin.closings.form.title', { type: closable_type, title: closable.title })"
        :description="$t('admin.closings.form.description')"
    />

    <form class="max-w-md mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="start_date" field-key="admin.closings.form.fields.start_date"></FormLabel>
                <input
                    id="start_date"
                    v-model="form.start_date"
                    type="text"
                    name="start_date"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    :placeholder="$t('admin.closings.form.fields.end_time.placeholder')"
                />
                <FormValidationError :message="form.errors.end_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: Description -->
        <div class="mb-6">
            <FormLabel field="description" field-key="admin.closings.form.fields.description"></FormLabel>
            <input
                id="description"
                v-model="form.description"
                type="text"
                name="description"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                :placeholder="$t('admin.closings.form.fields.description.placeholder')"
            />
            <FormValidationError :message="form.errors.description"></FormValidationError>
        </div>

        <div class="mb-6">
            <button
                type="submit"
                class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500"
                :disabled="form.processing"
            >
                {{ $t("admin.closings.form.actions.submit") }}
            </button>
        </div>
    </form>
</template>
<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

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
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const processing = ref(false);

const form = useForm({
    id: props.closing.id ?? "",
    start_date: props.closing.start_date ?? "",
    start_time: props.closing.start_time ?? "",
    end_date: props.closing.end_date ?? "",
    end_time: props.closing.end_time ?? "",
    description: props.closing.description ?? "",
    closable_id: props.closable.id,
    closable_type: props.closable_type,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    processing.value = true;
    if (form.id) {
        form.post("/admin/closing/update");
    } else {
        form.post("/admin/closing/store");
    }
};
</script>
