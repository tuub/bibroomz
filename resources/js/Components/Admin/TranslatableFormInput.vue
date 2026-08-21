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
                class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
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
                class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
                @change="$emit('update:model-value', input)"
            />
        </template>
    </TranslatableFormField>
</template>

<script setup lang="ts">
import TranslatableFormField from "@/Components/Admin/TranslatableFormField.vue";

import { ref } from "vue";

const props = withDefaults(
    defineProps<{
        modelValue: Record<string, string> | unknown[];
        field: string;
        fieldKey: string;
        placeholder?: string;
        required?: boolean;
        languages: string[];
        errors?: Record<string, string | undefined>;
        type?: string;
        rows?: string;
    }>(),
    {
        placeholder: "",
        required: false,
        errors: () => ({}),
        type: "input",
        rows: "10",
    },
);

defineEmits<{
    (event: "update:model-value", value: Record<string, string>): void;
}>();

const supportedTypes = ["input", "textarea"];
if (!supportedTypes.includes(props.type)) {
    console.warn(`TranslatableFormInput: unsupported type "${props.type}"; falling back to a text input.`);
}

const input = ref<Record<string, string>>(Array.isArray(props.modelValue) ? {} : props.modelValue);
</script>
