<template>
    <FormLayout :title="$t('admin.user_groups.form.title')" :description="$t('admin.user_groups.form.description')">
        <!-- Select: Institution -->
        <div v-if="!user_group?.id">
            <FormLabel field="institution_id" field-key="admin.user_groups.form.fields.institution"></FormLabel>
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
            field-key="admin.user_groups.form.fields.title"
            :placeholder="$t('admin.user_groups.form.fields.title.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <FormAction :form="form" model="user_group" cancel-route="admin.user_group.index" />
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    // eslint-disable-next-line vue/prop-name-casing
    user_group: {
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
    id: props.user_group.id ?? "",
    institution_id: props.user_group.institution_id ?? "",
    title: props.user_group.title ?? {},
});
</script>
