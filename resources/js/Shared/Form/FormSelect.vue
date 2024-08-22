<template>
    <div>
        <FormLabel :field="field" :field-key="fieldKey"></FormLabel>

        <select
            :id="field"
            v-model="model"
            :name="field"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
        >
            <option :value="placeholder.value">
                {{ $t("admin.general.form.choose") }}
            </option>

            <option v-for="option in options" :key="option.key" :value="option.value">
                {{ option.label }}
            </option>
        </select>

        <FormValidationError v-if="error" :message="error"></FormValidationError>
    </div>
</template>

<script setup>
import FormLabel from "./FormLabel.vue";
import FormValidationError from "./FormValidationError.vue";

defineProps({
    field: {
        type: String,
        required: true,
    },
    fieldKey: {
        type: String,
        required: true,
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: Object,
        default: () => ({
            value: "",
        }),
    },
    error: {
        type: String,
        default: null,
    },
});

const model = defineModel({
    type: String,
    required: true,
});
</script>
