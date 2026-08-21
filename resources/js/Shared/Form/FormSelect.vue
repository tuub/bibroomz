<template>
    <div>
        <FormLabel :field="field" :field-key="fieldKey"></FormLabel>

        <select
            :id="field"
            v-model="model"
            :name="field"
            class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
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

<script setup lang="ts">
import FormLabel from "./FormLabel.vue";
import FormValidationError from "./FormValidationError.vue";

withDefaults(
    defineProps<{
        field: string;
        fieldKey: string;
        options: { key: number | string; value: string; label?: string }[];
        placeholder?: { value: string };
        error?: string | null;
    }>(),
    {
        placeholder: () => ({ value: "" }),
        error: null,
    },
);

const model = defineModel<string | number>({ required: true });
</script>
