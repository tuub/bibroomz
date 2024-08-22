<template>
    <FormLayout
        :title="$t('admin.resource_groups.form.title')"
        :description="$t('admin.resource_groups.form.description')"
    >
        <!-- Select: Institution -->
        <div>
            <FormLabel field="institution_id" field-key="admin.resource_groups.form.fields.institution"></FormLabel>
            <select
                id="institution_id"
                v-model="form.institution_id"
                name="institution_id"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                required
            >
                <option value="">
                    {{ $t("admin.general.form.choose") }}
                </option>

                <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                    {{ translate(institution.title) }}
                </option>
            </select>
            <FormValidationError
                v-if="form.errors.institution_id"
                :message="form.errors.institution_id"
            ></FormValidationError>
        </div>

        <!-- Input: Title -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.resource_groups.form.fields.title"
            :placeholder="$t('admin.resource_groups.form.fields.title.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Slug -->
        <FormInput
            v-model="form.slug"
            field="slug"
            field-key="admin.resource_groups.form.fields.slug"
            :error="form.errors.slug"
            :is-required="true"
        />

        <!-- Input: Term singular -->
        <TranslatableFormInput
            v-model="form.term_singular"
            field="term_singular"
            field-key="admin.resource_groups.form.fields.term_singular"
            :placeholder="$t('admin.resource_groups.form.fields.term_singular.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Term plural -->
        <TranslatableFormInput
            v-model="form.term_plural"
            field="term_plural"
            field-key="admin.resource_groups.form.fields.term_plural"
            :placeholder="$t('admin.resource_groups.form.fields.term_plural.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Textarea: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.resource_groups.form.fields.description"
            :placeholder="$t('admin.resource_groups.form.fields.description.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="4"
        ></TranslatableFormInput>

        <!-- Checkbox: Is active -->
        <div class="space-x-2">
            <ToggleSwitch model="form.is_active" input-id="is_active" />
            <FormLabel field="is_active" field-key="admin.resource_groups.form.fields.is_active" class="inline-block" />
            <FormValidationError :message="form.errors.is_active"></FormValidationError>
        </div>

        <FormAction :form="form" model="resource_group" cancel-route="admin.resource_group.index" />
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import ToggleSwitch from "primevue/toggleswitch";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    // eslint-disable-next-line vue/prop-name-casing
    resource_group: {
        type: Object,
        default: () => ({}),
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const form = useForm({
    id: props.resource_group?.id ?? "",
    institution_id: props.resource_group?.institution_id ?? "",
    title: props.resource_group?.title ?? {},
    slug: props.resource_group?.slug ?? "",
    term_singular: props.resource_group?.term_singular ?? {},
    term_plural: props.resource_group?.term_plural ?? {},
    description: props.resource_group?.description ?? {},
    is_active: props.resource_group?.is_active ?? false,
});
</script>
