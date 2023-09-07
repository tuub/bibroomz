<template>
    <PageHead :title="$t('admin.permissions.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.permissions.form.title')" :description="$t('admin.permissions.form.description')" />

    <form class="max-w-md mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel field="name" field-key="admin.permissions.form.fields.name"></FormLabel>
            <input
                id="name"
                v-model="form.name"
                type="text"
                name="name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
            <FormValidationError :message="form.errors.name"></FormValidationError>
        </div>

        <!-- Input: Description -->
        <div class="mb-6">
            <FormLabel field="description" field-key="admin.permissions.form.fields.description"></FormLabel>
            <input
                id="description"
                v-model="form.description"
                type="text"
                name="description"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
            <FormValidationError :message="form.errors.description"></FormValidationError>
        </div>

        <div class="mb-6">
            <button
                type="submit"
                class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500"
                :disabled="isProcessing"
            >
                {{ $t("admin.permissions.form.actions.submit") }}
            </button>
        </div>
    </form>
</template>
<script setup>
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    permission: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isProcessing = ref(false);

const form = useForm({
    id: props.permission.id ?? "",
    name: props.permission.name ?? "",
    description: props.permission.description ?? "",
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    isProcessing.value = true;

    if (form.id) {
        form.post("/admin/permission/update/" + form.id);
    } else {
        form.post("/admin/permission/store");
    }

    isProcessing.value = false;
};
</script>
