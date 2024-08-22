<template>
    <FormLayout :title="title" :description="$t('admin.settings.form.description')">
        <FormInput v-model="form.key" field="key" field-key="admin.settings.form.fields.key" :error="form.errors.key" />

        <FormInput
            v-model="form.value"
            field="value"
            field-key="admin.settings.form.fields.value"
            :error="form.errors.value"
        />

        <FormAction
            :form="form"
            model="setting"
            cancel-route="admin.setting.index"
            :cancel-route-params="{ settingable_id: settingable.id, settingable_type: settingable_type }"
        />
    </FormLayout>
</template>

<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

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
// Variables
// ------------------------------------------------
const form = useForm({
    id: props.setting.id ?? "",
    key: props.setting.key ?? "",
    value: props.setting.value ?? "",
    settingable_id: props.settingable.id,
    settingable_type: props.settingable_type,
});

const translate = useAppStore().translate;

const title = computed(() =>
    trans("admin.settings.form.title", {
        type: trans("admin.settings.types." + props.settingable_type),
        title: translate(props.settingable.title),
    }),
);
</script>
