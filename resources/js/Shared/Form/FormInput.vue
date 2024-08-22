<template>
    <div class="space-y-2">
        <FormLabel :field="field" :field-key="fieldKey"></FormLabel>

        <input
            :id="field"
            v-model="model"
            :name="field"
            :placeholder="$t(`${props.fieldKey}.placeholder`)"
            :type="type"
            :disabled="isDisabled"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
        />

        <FormValidationError v-if="error" :message="error"></FormValidationError>
    </div>
</template>

<script setup>
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

const props = defineProps({
    field: {
        type: String,
        required: true,
    },
    fieldKey: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: "text",
    },
    isDisabled: {
        type: Boolean,
        default: false,
    },
    isRequired: {
        type: Boolean,
        default: false,
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
