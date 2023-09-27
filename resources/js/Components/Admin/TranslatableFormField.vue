<template>
    <div v-for="language in languages" :key="language" class="mb-6">
        <FormLabel :field="`${field}-${language}`" :field-key="fieldKey" :language="language"></FormLabel>
        <slot :language="language"></slot>
        <FormValidationError :message="errors[`${field}`]"></FormValidationError>
        <FormValidationError :message="errors[`${field}.${language}`]"></FormValidationError>
    </div>
</template>

<script setup>
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

defineProps({
    field: {
        type: String,
        required: true,
    },
    fieldKey: {
        type: String,
        required: true,
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
</script>
