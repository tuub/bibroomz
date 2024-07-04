<template>
    <PageHead
        :title="
            $t('admin.settings.form.title', {
                type: $t('admin.settings.types.' + settingable_type),
                title: 'hoo',
            })
        "
        page-type="admin"
    />

    <form class="max-w mx-auto mt-8">
        <!-- Input: Key -->
        <div class="mb-6">
            <FormLabel field="key" field-key="admin.settings.form.fields.key"></FormLabel>
            <input
                id="key"
                v-model="form.key"
                type="text"
                name="key"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.key"></FormValidationError>
        </div>

        <!-- Input: Value -->
        <div class="mb-6">
            <FormLabel field="value" field-key="admin.settings.form.fields.value"></FormLabel>
            <input
                id="value"
                v-model="form.value"
                type="text"
                name="value"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.value"></FormValidationError>
        </div>

        <FormAction
            :form="form"
            model="setting"
            cancel-route="admin.setting.index"
            :cancel-route-params="{ settingable_id: settingable.id, settingable_type: settingable_type }"
        />
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    setting: {
        type: Object,
        default: () => ({}),
    },
    settingable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    settingable_type: {
        type: String,
        default: "",
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
    id: props.setting.id ?? "",
    key: props.setting.key ?? "",
    value: props.setting.value ?? "",
    settingable_id: props.settingable.id,
    settingable_type: props.settingable_type,
});
</script>
