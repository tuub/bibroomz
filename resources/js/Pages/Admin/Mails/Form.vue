<template>
    <FormLayout :title="$t('admin.mails.form.title')" :description="$t('admin.mails.form.description')">
        <!-- Select: Mail Type -->
        <FormSelect
            v-if="!mail.id"
            v-model="form.mail_type_id"
            field="mail_type_id"
            field-key="admin.mails.form.fields.mail_type"
            :options="
                mail_types.map((mail_type) => ({
                    key: mail_type.id,
                    value: mail_type.id.toString(),
                    label: $t('admin.mails.mail_types.' + mail_type.key),
                }))
            "
            :error="form.errors.mail_type_id"
        />

        <!-- Input: Subject -->
        <TranslatableFormInput
            v-model="form.subject"
            field="subject"
            field-key="admin.mails.form.fields.subject"
            :placeholder="$t('admin.mails.form.fields.subject.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Salutation -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.mails.form.fields.title"
            :placeholder="$t('admin.mails.form.fields.title.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Salutation -->
        <TranslatableFormInput
            v-model="form.salutation"
            field="salutation"
            field-key="admin.mails.form.fields.salutation"
            :placeholder="$t('admin.mails.form.fields.salutation.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Textarea: Intro -->
        <TranslatableFormInput
            v-model="form.intro"
            field="intro"
            field-key="admin.mails.form.fields.intro"
            :placeholder="$t('admin.mails.form.fields.intro.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="10"
        ></TranslatableFormInput>

        <!-- Textarea: Outro -->
        <TranslatableFormInput
            v-model="form.outro"
            field="outro"
            field-key="admin.mails.form.fields.outro"
            :placeholder="$t('admin.mails.form.fields.outro.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="10"
        ></TranslatableFormInput>

        <!-- Input: Action URI -->
        <FormInput
            v-model="form.action_uri"
            field="action_uri"
            field-key="admin.mails.form.fields.action_uri"
            :error="form.errors.action_uri"
        />

        <!-- Input: Action URI Label -->
        <FormInput
            v-model="form.action_uri_label"
            field="action_uri_label"
            field-key="admin.mails.form.fields.action_uri_label"
            :error="form.errors.action_uri_label"
        />

        <!-- Textarea: Farewell -->
        <TranslatableFormInput
            v-model="form.farewell"
            field="farewell"
            field-key="admin.mails.form.fields.farewell"
            :placeholder="$t('admin.mails.form.fields.farewell.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="10"
        ></TranslatableFormInput>

        <!-- Checkbox: Is active -->
        <div class="space-x-3">
            <ToggleSwitch v-model="form.is_active" input-id="is_active" />
            <FormLabel field="is_active" field-key="admin.mails.form.fields.is_active" class="inline-block" />
            <FormValidationError :message="form.errors.is_active"></FormValidationError>
        </div>

        <FormAction
            :form="form"
            model="mail"
            cancel-route="admin.mail.index"
            :cancel-route-params="{ institution_id: institution_id }"
        />
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormSelect from "@/Shared/Form/FormSelect.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    // eslint-disable-next-line vue/prop-name-casing
    institution_id: {
        type: String,
        required: true,
    },
    mail: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    mail_types: {
        type: Array,
        default: () => [],
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const form = useForm({
    id: props.mail?.id ?? "",
    institution_id: props.institution_id,
    mail_type_id: props.mail?.mail_type_id ?? "",
    subject: props.mail?.subject ?? {},
    title: props.mail?.title ?? {},
    salutation: props.mail?.salutation ?? {},
    intro: props.mail?.intro ?? {},
    outro: props.mail?.outro ?? {},
    action_uri: props.mail?.action_uri ?? "",
    action_uri_label: props.mail?.action_uri_label ?? "",
    farewell: props.mail?.farewell ?? {},
    is_active: props.mail?.is_active ?? false,
});
</script>
