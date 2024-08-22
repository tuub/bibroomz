<template>
    <FormLayout :title="$t('admin.users.form.title')" :description="$t('admin.users.form.description')">
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_system_user" input-id="is_system_user" disabled />
            <FormLabel field="is_admin" field-key="admin.users.form.fields.is_system_user" class="inline-block" />
            <FormValidationError :message="form.errors.is_system_user"></FormValidationError>
        </div>

        <FormInput
            v-model="form.name"
            field="name"
            field-key="admin.users.form.fields.name"
            :error="form.errors.name"
            :is-disabled="isDisabled"
        />

        <FormInput
            v-model="form.email"
            field="email"
            field-key="admin.users.form.fields.email"
            :error="form.errors.email"
            :is-disabled="isDisabled"
        />

        <div v-if="isSystemUser">
            <div class="space-x-2">
                <ToggleSwitch v-model="form.is_set_password" input-id="is_set_password" />
                <FormLabel
                    field="is_set_password"
                    field-key="admin.users.form.fields.is_set_password"
                    class="inline-block"
                />
                <FormValidationError :message="form.errors.is_set_password"></FormValidationError>
            </div>

            <div v-if="isSetPassword">
                <FormInput
                    v-if="isExistingUser"
                    v-model="form.current_password"
                    field="current_password"
                    field-key="admin.users.form.fields.current_password"
                    type="password"
                    :error="form.errors.current_password"
                />

                <FormInput
                    v-model="form.password"
                    field="password"
                    field-key="admin.users.form.fields.password"
                    type="password"
                    :error="form.errors.password"
                />

                <FormInput
                    v-model="form.password_confirm"
                    field="password_confirm"
                    field-key="admin.users.form.fields.password_confirm"
                    type="password"
                    :error="form.errors.password_confirm"
                />

                <button @click.prevent="togglePassword">{{ $t("admin.general.form.toggle_password") }}</button>
            </div>
        </div>

        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_admin" input-id="is_admin" />
            <FormLabel field="is_admin" field-key="admin.users.form.fields.is_admin" class="inline-block" />
            <FormValidationError :message="form.errors.is_admin"></FormValidationError>
        </div>

        <fieldset :hidden="form.is_admin">
            <legend class="space-y-2">
                <div class="text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.users.form.fields.roles.label") }}
                </div>

                <div class="text-xs">
                    {{ $t("admin.users.form.fields.roles.hint") }}
                </div>
            </legend>

            <div v-for="institution in institutions" :key="institution.id" class="grid">
                <span>{{ institution.title }}</span>

                <MultiSelect
                    v-model="selectedRoles[institution.id]"
                    :options="roles"
                    :option-label="(role) => translate(role.name)"
                    :option-value="(role) => role.id"
                    :show-toggle-all="false"
                    :max-selected-labels="2"
                    :invalid="form.errors.roles"
                    display="chip"
                />
            </div>

            <FormValidationError :message="form.errors.roles"></FormValidationError>
        </fieldset>

        <FormAction :form="form" model="user" cancel-route="admin.user.index"></FormAction>
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import MultiSelect from "primevue/multiselect";
import ToggleSwitch from "primevue/toggleswitch";
import { computed, ref } from "vue";

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

    // eslint-disable-next-line vue/prop-name-casing
    is_system_user: {
        type: Boolean,
        default: false,
    },

    // eslint-disable-next-line vue/prop-name-casing
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

let selectedRoles = ref({});
if (props.user.roles) {
    for (const { institution_id, role_id } of props.user.roles) {
        let institutionRoles = selectedRoles.value[institution_id] ?? [];
        institutionRoles.push(role_id);
        selectedRoles.value[institution_id] = institutionRoles;
    }
}

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
    roles: computed(() => {
        let roles = [];
        for (const institutionId of Object.keys(selectedRoles.value)) {
            roles.push({ institution_id: institutionId, role_id: selectedRoles.value[institutionId] });
        }
        return roles;
    }),
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
const togglePassword = () => {
    const passwordElementIds = ["current_password", "password", "password_confirm"];

    for (const elementId of passwordElementIds) {
        const passwordElement = document.getElementById(elementId);
        passwordElement.type = passwordElement.type === "password" ? "text" : "password";
    }
};
</script>
