<template>
    <PageHead :title="$t('admin.users.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.form.title')" :description="$t('admin.users.form.description')" />

    <form class="max-w mx-auto mt-8">
        <!-- Checkbox: Is System User? -->
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="form.is_system_user" type="checkbox" class="peer sr-only" disabled />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.users.form.fields.is_system_user.label") }}
                </span>
            </label>
            <div class="mb-2 text-xs">{{ $t("admin.users.form.fields.is_system_user.hint") }}</div>
            <FormValidationError :message="form.errors.is_system_user"></FormValidationError>
        </div>

        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel field="name" field-key="admin.users.form.fields.name"></FormLabel>
            <input
                id="name"
                v-model="form.name"
                type="text"
                name="name"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.users.form.fields.name.placeholder')"
                :disabled="isDisabled"
            />
            <FormValidationError :message="form.errors.name"></FormValidationError>
        </div>

        <!-- Input: E-Mail -->
        <div class="mb-6">
            <FormLabel field="email" field-key="admin.users.form.fields.email"></FormLabel>
            <input
                id="email"
                v-model="form.email"
                type="text"
                name="email"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.users.form.fields.email.placeholder')"
                :disabled="isDisabled"
            />
            <FormValidationError :message="form.errors.email"></FormValidationError>
        </div>

        <!-- Checkbox: Set password? -->
        <div v-if="isSystemUser" class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="form.is_set_password" type="checkbox" class="peer sr-only" />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.users.form.fields.is_set_password.label") }}
                </span>
            </label>
            <div class="mb-2 text-xs">{{ $t("admin.users.form.fields.is_set_password.hint") }}</div>
            <FormValidationError :message="form.errors.is_set_password"></FormValidationError>
        </div>

        <!-- Input: Current Password -->
        <div v-if="isSystemUser && isExistingUser && isSetPassword" class="mb-6">
            <FormLabel field="password" field-key="admin.users.form.fields.current_password"></FormLabel>
            <input
                id="current_password"
                v-model="form.current_password"
                type="password"
                name="password"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.users.form.fields.password.placeholder')"
            />
            <a rel="current_password" @click="togglePassword">{{ $t("admin.general.form.toggle_password") }}</a>
            <FormValidationError :message="form.errors.current_password"></FormValidationError>
        </div>

        <!-- Input: Password -->
        <div v-if="isSystemUser && isSetPassword" class="mb-6">
            <FormLabel field="password" field-key="admin.users.form.fields.password"></FormLabel>
            <input
                id="password"
                v-model="form.password"
                type="password"
                name="password"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.users.form.fields.password.placeholder')"
            />
            <a rel="password" @click="togglePassword">{{ $t("admin.general.form.toggle_password") }}</a>
            <FormValidationError :message="form.errors.password"></FormValidationError>
        </div>

        <!-- Input: Password Confirm -->
        <div v-if="isSystemUser && isSetPassword" class="mb-6">
            <FormLabel field="password_confirm" field-key="admin.users.form.fields.password_confirm"></FormLabel>
            <input
                id="password_confirm"
                v-model="form.password_confirm"
                type="password"
                name="password_confirm"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                :placeholder="$t('admin.users.form.fields.password_confirm.placeholder')"
            />
            <a rel="password_confirm" @click="togglePassword">{{ $t("admin.general.form.toggle_password") }}</a>
            <FormValidationError :message="form.errors.password_confirm"></FormValidationError>
        </div>

        <!-- Checkbox: Is Admin? -->
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="form.is_admin" type="checkbox" class="peer sr-only" />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.users.form.fields.is_admin.label") }}
                </span>
            </label>
            <div class="mb-2 text-xs">{{ $t("admin.users.form.fields.is_admin.hint") }}</div>
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
                            <div class="flex h-5 items-center">
                                <input
                                    :id="`role-checkbox-${institutionIndex}-${roleIndex}`"
                                    v-model="form.roles"
                                    :value="{ role_id: role.id, institution_id: institution.id }"
                                    :aria-describedby="`role-checkbox-text-${institutionIndex}-${roleIndex}`"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-red-600 focus:ring-2 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-red-600"
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
import { computed } from "vue";

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
    is_system_user: {
        type: Boolean,
        default: false,
    },
    is_set_password: {
        type: Boolean,
        default: false,
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
    is_admin: props.user.is_admin ?? false,
    is_system_user: props.user.is_system_user ?? props.is_system_user,
    is_set_password: props.is_set_password ?? false,
    current_password: props.user.current_password ?? "",
    password: props.user.password ?? "",
    password_confirm: props.user.password_confirm ?? "",
    banned_at: props.user.banned_at ?? "",
    roles: props.user.roles ?? [],
});

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isDisabled = computed(() => {
    return !form.is_system_user;
});

const isExistingUser = computed(() => {
    return !!form.id;
});

const isSystemUser = computed(() => {
    return form.is_system_user;
});

const isSetPassword = computed(() => {
    return form.is_set_password;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const togglePassword = (event) => {
    const passwordField = document.getElementById(event.target.rel);
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
};
</script>
