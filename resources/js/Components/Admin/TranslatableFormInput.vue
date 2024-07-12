<template>
    <TranslatableFormField :field="field" :field-key="fieldKey" :languages="languages" :errors="errors">
        <template #default="{ language }">
            <textarea
                v-if="type === 'textarea'"
                :id="`${field}-${language}`"
                v-model.lazy="input[language]"
                :placeholder="placeholder"
                :required="required"
                :name="field"
                :rows="rows"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                @change="$emit('update:model-value', input)"
            />
            <input
                v-else
                :id="`${field}-${language}`"
                v-model.lazy="input[language]"
                :placeholder="placeholder"
                :required="required"
                :name="field"
                type="text"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                @change="$emit('update:model-value', input)"
            />
        </template>
    </TranslatableFormField>
</template>

<script setup>
import TranslatableFormField from "@/Components/Admin/TranslatableFormField.vue";

import { ref } from "vue";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    field: {
        type: String,
        required: true,
    },
    fieldKey: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: "",
    },
    required: {
        type: Boolean,
        default: false,
    },
    languages: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    type: {
        type: String,
        default: "input",
        validator: (value) => ["input", "textarea"].includes(value),
    },
    rows: {
        type: String,
        default: "10",
    },
});

defineEmits(["update:model-value"]);

const input = Array.isArray(props.modelValue) ? {} : ref(props.modelValue);
</script>
