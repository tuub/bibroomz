<template>
    <PageHead :title="$t('admin.resource_groups.form.title')" page-type="admin" />
    <BodyHead
        :title="$t('admin.resource_groups.form.title')"
        :description="$t('admin.resource_groups.form.description')"
    />

    <form class="max-w mx-auto mt-8">
        <!-- Select: Institution -->
        <div class="mb-6">
            <FormLabel field="institution_id" field-key="admin.resource_groups.form.fields.institution"></FormLabel>
            <select
                id="institution_id"
                v-model="form.institution_id"
                name="institution_id"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
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

        <!-- Input: Name -->
        <TranslatableFormInput
            v-model="form.name"
            field="name"
            field-key="admin.resource_groups.form.fields.name"
            :placeholder="$t('admin.resource_groups.form.fields.name.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Slug -->
        <div class="mb-6">
            <FormLabel field="slug" field-key="admin.resource_groups.form.fields.slug"></FormLabel>
            <input
                id="slug"
                v-model="form.slug"
                type="text"
                name="slug"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                :placeholder="$t('admin.resource_groups.form.fields.slug.placeholder')"
            />
            <FormValidationError :message="form.errors.slug"></FormValidationError>
        </div>

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
        <TranslatableFormField
            field="description"
            field-key="admin.resource_groups.form.fields.description"
            :languages="languages"
            :errors="form.errors"
        >
            <template #default="{ language }">
                <textarea
                    :id="`description-${language}`"
                    v-model="form.description[language]"
                    name="description"
                    rows="4"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    :placeholder="$t('admin.resource_groups.form.fields.description.placeholder')"
                ></textarea>
            </template>
        </TranslatableFormField>

        <!-- Checkbox: Is active -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="sr-only peer" />
                <div
                    class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"
                ></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t("admin.resource_groups.form.fields.is_active.label") }}
                </span>
            </label>
            <FormValidationError v-if="form.errors.is_active" :message="form.errors.is_active"></FormValidationError>
        </div>

        <FormAction :form="form" model="resource_group" cancel-route="admin.resource_group.index" />
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormField from "@/Components/Admin/TranslatableFormField.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";

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
    name: props.resource_group?.name ?? {},
    slug: props.resource_group?.slug ?? "",
    term_singular: props.resource_group?.term_singular ?? {},
    term_plural: props.resource_group?.term_plural ?? {},
    description: props.resource_group?.description ?? {},
    is_active: props.resource_group?.is_active ?? false,
});
</script>
