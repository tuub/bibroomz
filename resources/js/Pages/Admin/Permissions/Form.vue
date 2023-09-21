<template>
    <PageHead :title="$t('admin.permissions.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.permissions.form.title')" :description="$t('admin.permissions.form.description')" />

    <form class="max-w mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Name -->
        <TranslatableFormInput
            v-model="form.name"
            field="name"
            field-key="admin.permissions.form.fields.name"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.permissions.form.fields.description"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <div class="mb-6">
            <button
                type="submit"
                class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500"
                :disabled="isProcessing"
            >
                {{ $t("admin.permissions.form.actions.submit") }}
            </button>
        </div>
    </form>
</template>
<script setup>
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    permission: {
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
const isProcessing = ref(false);

const form = useForm({
    id: props.permission.id ?? "",
    name: props.permission.name ?? {},
    description: props.permission.description ?? {},
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    isProcessing.value = true;

    if (form.id) {
        form.post("/admin/permission/update/" + form.id);
    } else {
        form.post("/admin/permission/store");
    }

    isProcessing.value = false;
};
</script>
