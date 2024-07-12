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
        <div class="mb-6">
            <FormLabel field="slug" field-key="admin.resource_groups.form.fields.slug"></FormLabel>
            <input
                id="slug"
                v-model="form.slug"
                type="text"
                name="slug"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
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
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="peer sr-only" />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
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
    title: props.resource_group?.title ?? {},
    slug: props.resource_group?.slug ?? "",
    term_singular: props.resource_group?.term_singular ?? {},
    term_plural: props.resource_group?.term_plural ?? {},
    description: props.resource_group?.description ?? {},
    is_active: props.resource_group?.is_active ?? false,
});
</script>
