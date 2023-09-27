<template>
    <PageHead title="Admin Settings Form" page-type="admin" />

    <h1 class="text-3xl">Setting Form</h1>

    <form class="max-w mx-auto mt-8">
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
            <input
                id="value"
                v-model="form.value"
                type="text"
                name="value"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.value"></FormValidationError>
        </div>

        <FormAction :form="form" model="setting" cancel-route="admin.setting.index"></FormAction>
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    setting: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isProcessing = ref(false);

const form = useForm({
    id: props.setting.id ?? "",
    key: props.setting.key ?? "",
    value: props.setting.value ?? "",
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    isProcessing.value = true;
    if (form.id) {
        form.post("/admin/setting/update");
    }
    isProcessing.value = false;
};
</script>
