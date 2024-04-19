<template>
    <PageHead :title="$t('admin.mails.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.mails.form.title')" :description="$t('admin.mails.form.description')" />

    <form class="max-w mx-auto mt-8">
        <!-- Select: Mail Type -->
        <div v-if="!mail.id" class="mb-6">
            <FormLabel field="mail_type_id" field-key="admin.mails.form.fields.mail_type"></FormLabel>
            <select
                id="mail_type_id"
                v-model="form.mail_type_id"
                name="mail_type_id"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
            >
                <option value="">
                    {{ $t("admin.general.form.choose") }}
                </option>

                <option v-for="mail_type in mail_types" :key="mail_type.id" :value="mail_type.id">
                    {{ $t("admin.mails.mail_types." + mail_type.key) }}
                </option>
            </select>
            <FormValidationError
                v-if="form.errors.mail_type_id"
                :message="form.errors.mail_type_id"
            ></FormValidationError>
        </div>

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
        <div class="mb-6">
            <FormLabel field="action_uri" field-key="admin.mails.form.fields.action_uri"></FormLabel>
            <input
                id="action_uri"
                v-model="form.action_uri"
                type="text"
                name="action_uri"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                :placeholder="$t('admin.mails.form.fields.action_uri.placeholder')"
            />
            <FormValidationError :message="form.errors.action_uri"></FormValidationError>
        </div>

        <!-- Input: Action URI Label -->
        <div class="mb-6">
            <FormLabel field="action_uri_label" field-key="admin.mails.form.fields.action_uri_label"></FormLabel>
            <input
                id="action_uri_label"
                v-model="form.action_uri_label"
                type="text"
                name="action_uri_label"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                :placeholder="$t('admin.mails.form.fields.action_uri_label.placeholder')"
            />
            <FormValidationError :message="form.errors.action_uri_label"></FormValidationError>
        </div>

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
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="sr-only peer" />
                <div
                    class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"
                ></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t("admin.mails.form.fields.is_active.label") }}
                </span>
            </label>
            <FormValidationError v-if="form.errors.is_active" :message="form.errors.is_active"></FormValidationError>
        </div>

        <FormAction
            :form="form"
            model="mail"
            cancel-route="admin.mail.index"
            :cancel-route-params="{ institution_id: institution_id }"
        />
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

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
        type: Object,
        default: () => ({}),
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
