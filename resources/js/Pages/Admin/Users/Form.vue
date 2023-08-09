<template>
    <PageHead :title="$t('admin.users.form.title')" page_type="admin" />
    <BodyHead
        :title="$t('admin.users.form.title')"
        :description="$t('admin.users.form.description')"
    />

    <form class="max-w-md mx-auto mt-8" @submit.prevent="submitForm">
        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel
                field="name"
                field-key="admin.users.form.fields.name"
            ></FormLabel>
            <input
                id="name"
                v-model="form.name"
                type="text"
                name="name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                disabled
                readonly
            />
            <FormValidationError
                :message="form.errors.name"
            ></FormValidationError>
        </div>

        <!-- Input: E-Mail -->
        <div class="mb-6">
            <FormLabel
                field="email"
                field-key="admin.users.form.fields.email"
            ></FormLabel>
            <input
                id="email"
                v-model="form.email"
                type="text"
                name="email"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder=""
                disabled
                readonly
            />
            <FormValidationError
                :message="form.errors.email"
            ></FormValidationError>
        </div>

        <!-- Checkbox: Is Admin? -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input
                    v-model="form.is_admin"
                    type="checkbox"
                    class="sr-only peer"
                />
                <div
                    class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"
                ></div>
                <span
                    class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase"
                >
                    {{ $t("admin.users.form.fields.is_admin.label") }}
                </span>
            </label>

            <div class="text-xs mb-2">{{ $t('admin.users.form.fields.is_admin.hint') }}</div>
        </div>

        <!-- Institution Admin -->
        <div class="mb-6" :hidden="form.is_admin">
            <div>
                <FormLabel
                    field-key="admin.users.form.fields.institution_admin"
                ></FormLabel>
            </div>
            <ul>
                <li
                    v-for="(institution, index) in institutions"
                    :key="institution.id"
                >
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input
                                :id="`institution-admin-checkbox-${index}`"
                                v-model="form.institution_admin[institution.id]"
                                :aria-describedby="`institution-admin-checkbox-text-${index}`"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                        </div>
                        <div class="ml-2 text-sm">
                            <label
                                :for="`institution-admin-checkbox-${index}`"
                                class="font-medium text-gray-900 dark:text-gray-300"
                                >{{ institution.short_title }}</label
                            >
                            <p
                                :id="`institution-admin-checkbox-text-${index}`"
                                class="text-xs font-normal text-gray-500 dark:text-gray-300"
                            >
                                {{ institution.title }}
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
                {{ $t("admin.resources.form.actions.submit") }}
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
// eslint-disable-next-line @typescript-eslint/no-unused-vars
let props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    institutionAdmin: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
// let institution_admin = {};
// for (let institution of props.institutions) {
//     institution_admin[institution.id] = false;
// }

let isProcessing = ref(false);

let form = useForm({
    id: props.user.id ?? "",
    name: props.user.name ?? "",
    email: props.user.email ?? "",
    is_admin: props.user.is_admin ?? "",
    banned_at: props.user.banned_at ?? "",
    institution_admin: props.institutionAdmin,
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = () => {
    isProcessing.value = true;
    if (form.id) {
        form.post("/admin/user/update");
    }
    isProcessing.value = false;
};
</script>
