<template>
    <PageHead :title="$t('admin.roles.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.roles.form.title')" :description="$t('admin.roles.form.description')" />

    <form class="max-w mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Name -->
        <TranslatableFormInput
            v-model="form.name"
            field="name"
            field-key="admin.roles.form.fields.name"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.roles.form.fields.description"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Checkbox: Permissions -->
        <div class="mb-6">
            <div>
                <FormLabel field-key="admin.roles.form.fields.permissions"></FormLabel>
            </div>
            <ul>
                <li v-for="(permission, index) in permissions" :key="permission.id">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input
                                :id="`permission-checkbox-${index}`"
                                v-model="form.permissions"
                                :value="permission.id"
                                :aria-describedby="`permission-checkbox-text-${index}`"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>
                        <div class="ml-2 text-sm">
                            <label
                                :for="`permission-checkbox-${index}`"
                                class="font-medium text-gray-900 dark:text-gray-300"
                                >{{ translate(permission.name) }}</label
                            >
                            <p
                                :id="`permission-checkbox-text-${index}`"
                                class="text-xs font-normal text-gray-500 dark:text-gray-300"
                            >
                                {{ translate(permission.description) }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <div class="mb-6">
            <button
                type="submit"
                class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500"
                :disabled="isProcessing"
            >
                {{ $t("admin.roles.form.actions.submit") }}
            </button>
        </div>
    </form>
</template>
<script setup>
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";
import { inject, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    role: {
        type: Object,
        default: () => ({}),
    },
    permissions: {
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
const translate = inject("translate");
const isProcessing = ref(false);

const form = useForm({
    id: props.role.id ?? "",
    name: props.role.name ?? {},
    description: props.role.description ?? {},
    permissions: props.role.permissions?.map((permission) => permission.id) ?? [],
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const submitForm = () => {
    isProcessing.value = true;

    if (form.id) {
        form.post("/admin/role/update/" + form.id);
    } else {
        form.post("/admin/role/store");
    }

    isProcessing.value = false;
};
</script>
