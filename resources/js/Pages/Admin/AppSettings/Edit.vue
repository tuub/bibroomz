<template>
    <FormLayout :title="$t('admin.app_settings.form.title')" :description="$t('admin.app_settings.form.description')">
        <FormInput
            v-for="key in Object.keys(settings)"
            :key="key"
            v-model="form[key]"
            :field="key"
            :field-key="`admin.app_settings.form.fields.${key}`"
            :error="form.errors[key]"
            :type="inputTypes[key]"
            :rows="5"
        />

        <FormAction :form="form" model="app_setting" action="update" cancel-route="admin.app_setting.index" />
    </FormLayout>
</template>

<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    inputTypes: {
        type: Object,
        required: true,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const form = useForm({ ...props.settings });
</script>
