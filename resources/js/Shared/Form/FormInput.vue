<template>
    <div class="space-y-2">
        <FormLabel :field="field" :field-key="fieldKey"></FormLabel>

        <textarea
            v-if="type === 'textarea'"
            :id="field"
            v-model="model"
            :name="field"
            :placeholder="$t(`${props.fieldKey}.placeholder`)"
            :disabled="isDisabled"
            :rows="rows"
            class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
        ></textarea>

        <input
            v-else
            :id="field"
            v-model="model"
            :name="field"
            :placeholder="$t(`${props.fieldKey}.placeholder`)"
            :type="type"
            :disabled="isDisabled"
            class="border-app-border bg-app-field text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub block w-full rounded-lg border p-2.5 text-sm"
        />

        <FormValidationError v-if="error" :message="error"></FormValidationError>
    </div>
</template>

<script setup lang="ts">
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

const props = withDefaults(
    defineProps<{
        field: string;
        fieldKey: string;
        type?: string;
        rows?: number;
        isDisabled?: boolean;
        isRequired?: boolean;
        error?: string | null;
    }>(),
    {
        type: "text",
        rows: 4,
        isDisabled: false,
        isRequired: false,
        error: null,
    },
);

const model = defineModel<string>({ required: true });
</script>
