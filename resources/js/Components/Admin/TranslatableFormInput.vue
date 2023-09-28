<template>
    <TranslatableFormField :field="field" :field-key="fieldKey" :languages="languages" :errors="errors">
        <template #default="{ language }">
            <input
                :id="`${field}-${language}`"
                v-model="input[language]"
                :placeholder="placeholder"
                :required="required"
                :name="field"
                type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
});

defineEmits(["update:model-value"]);

const input = Array.isArray(props.modelValue) ? {} : ref(props.modelValue);
</script>
