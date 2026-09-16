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
                class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
                @change="syncModel"
            />
            <input
                v-else
                :id="`${field}-${language}`"
                v-model.lazy="input[language]"
                :placeholder="placeholder"
                :required="required"
                :name="field"
                type="text"
                class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
                @change="syncModel"
            />
        </template>
    </TranslatableFormField>
</template>

<script setup lang="ts">
import TranslatableFormField from "@/Components/Admin/TranslatableFormField.vue";

import { ref } from "vue";

const props = withDefaults(
    defineProps<{
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

const model = defineModel<Record<string, string> | unknown[]>({ required: true });

const supportedTypes = ["input", "textarea"];
if (!supportedTypes.includes(props.type)) {
    console.warn(`TranslatableFormInput: unsupported type "${props.type}"; falling back to a text input.`);
}

const input = ref<Record<string, string>>(Array.isArray(model.value) ? {} : { ...model.value });

function syncModel(): void {
    model.value = input.value;
}
</script>
