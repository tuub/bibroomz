<template>
    <PageHead :title="$t('admin.permission_groups.form.title')" page-type="admin" />
    <BodyHead
        :title="$t('admin.permission_groups.form.title')"
        :description="$t('admin.permission_groups.form.description')"
    />

    <form class="max-w mx-auto mt-8">
        <!-- Input: Name -->
        <TranslatableFormInput
            v-model="form.name"
            field="name"
            field-key="admin.permission_groups.form.fields.name"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.permission_groups.form.fields.description"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <FormAction :form="form" model="permission_group" cancel-route="admin.permission_group.index"></FormAction>
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    permissionGroup: {
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
    id: props.permissionGroup.id ?? "",
    name: props.permissionGroup.name ?? {},
    description: props.permissionGroup.description ?? {},
});
</script>
