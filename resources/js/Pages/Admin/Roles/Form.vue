<template>
    <PageHead :title="$t('admin.roles.form.title')" page_type="admin" />
    <BodyHead :title="$t('admin.roles.form.title')" :description="$t('admin.roles.form.description')" />

    <form class="max-w-md mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel field="name" field-key="admin.roles.form.fields.name"></FormLabel>
            <input
                id="name"
                v-model="form.name"
                type="text"
                name="name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
            <FormValidationError :message="form.errors.name"></FormValidationError>
        </div>

        <!-- Input: Description -->
        <div class="mb-6">
            <FormLabel field="description" field-key="admin.roles.form.fields.description"></FormLabel>
            <input
                id="description"
                v-model="form.description"
                type="text"
                name="description"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            />
            <FormValidationError :message="form.errors.description"></FormValidationError>
        </div>

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
                                >{{ permission.name }}</label
                            >
                            <p
                                :id="`permission-checkbox-text-${index}`"
                                class="text-xs font-normal text-gray-500 dark:text-gray-300"
                            >
                                {{ permission.name }}
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
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

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
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isProcessing = ref(false);

const form = useForm({
    id: props.role.id ?? "",
    name: props.role.name ?? "",
    description: props.role.description ?? "",
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
