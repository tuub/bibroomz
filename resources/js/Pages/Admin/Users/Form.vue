<template>
    <PageHead :title="$t('admin.users.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.form.title')" :description="$t('admin.users.form.description')" />

    <form class="max-w mx-auto mt-8">
        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel field="name" field-key="admin.users.form.fields.name"></FormLabel>
            <input
                id="name"
                :value="form.name"
                type="text"
                name="name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                disabled
                readonly
            />
        </div>

        <!-- Input: E-Mail -->
        <div class="mb-6">
            <FormLabel field="email" field-key="admin.users.form.fields.email"></FormLabel>
            <input
                id="email"
                :value="form.email"
                type="text"
                name="email"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder=""
                disabled
                readonly
            />
        </div>

        <!-- Checkbox: Is Admin? -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_admin" type="checkbox" class="sr-only peer" />
                <div
                    class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-red-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"
                ></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t("admin.users.form.fields.is_admin.label") }}
                </span>
            </label>

            <div class="text-xs mb-2">{{ $t("admin.users.form.fields.is_admin.hint") }}</div>

            <FormValidationError :message="form.errors.is_admin"></FormValidationError>
        </div>

        <!-- Roles -->
        <div class="mb-6" :hidden="form.is_admin">
            <div>
                <FormLabel field-key="admin.users.form.fields.roles"></FormLabel>
            </div>

            <div v-for="(institution, institutionIndex) in institutions" :key="institution.id">
                <div class="">{{ institution.title }}</div>

                <ul class="px-2">
                    <li v-for="(role, roleIndex) in roles" :key="role.id">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input
                                    :id="`role-checkbox-${institutionIndex}-${roleIndex}`"
                                    v-model="form.roles"
                                    :value="{ role_id: role.id, institution_id: institution.id }"
                                    :aria-describedby="`role-checkbox-text-${institutionIndex}-${roleIndex}`"
                                    type="checkbox"
                                    class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                />
                            </div>
                            <div class="ml-2 text-sm">
                                <label
                                    :for="`role-checkbox-${institutionIndex}-${roleIndex}`"
                                    class="font-medium text-gray-900 dark:text-gray-300"
                                    >{{ translate(role.name) }}</label
                                >
                                <p
                                    :id="`role-checkbox-text-${institutionIndex}-${roleIndex}`"
                                    class="text-xs font-normal text-gray-500 dark:text-gray-300"
                                >
                                    {{ translate(role.description) }}
                                </p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <FormValidationError :message="form.errors.roles"></FormValidationError>
        </div>

        <FormAction :form="form" model="user" cancel-route="admin.user.index"></FormAction>
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
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
    user: {
        type: Object,
        default: () => ({}),
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
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
    id: props.user.id ?? "",
    name: props.user.name ?? "",
    email: props.user.email ?? "",
    is_admin: props.user.is_admin ?? "",
    banned_at: props.user.banned_at ?? "",
    roles: props.user.roles ?? [],
});
</script>
