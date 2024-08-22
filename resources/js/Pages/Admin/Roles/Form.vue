<template>
    <FormLayout :title="$t('admin.roles.form.title')" :description="$t('admin.roles.form.description')">
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
        <fieldset>
            <legend class="space-y-2">
                <div class="text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.roles.form.fields.permissions.label") }}
                </div>

                <div class="text-xs">
                    {{ $t("admin.roles.form.fields.permissions.hint") }}
                </div>
            </legend>

            <div
                v-for="group in [...groups].sort((a, b) => translate(a.name).localeCompare(translate(b.name)))"
                :key="group.id"
            >
                <div>
                    <input
                        :id="`group-checkbox-${group.id}`"
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-red-600 focus:ring-2 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-red-600"
                        type="checkbox"
                        :checked="isGroupChecked(group.id)"
                        :indeterminate="isGroupIndeterminate(group.id)"
                        @change="updateCheckedPermissions(group.id)"
                    />
                    <span class="pl-2 text-gray-600">{{ translate(group.name) }}</span>
                </div>

                <ul class="mb-2 ml-6">
                    <li v-for="permission in permissions.filter((x) => x.group_id === group.id)" :key="permission.id">
                        <LabeledCheckbox
                            :value="permission.id"
                            :checked="form.permissions.includes(permission.id)"
                            :label="translate(permission.name)"
                            :description="translate(permission.description)"
                            name="permission"
                            @update-checked="updatePermission($event)"
                        ></LabeledCheckbox>
                    </li>
                </ul>
            </div>

            <div>
                <div>
                    <input
                        id="no-group-checkbox"
                        class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-red-600 focus:ring-2 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-red-600"
                        type="checkbox"
                        :checked="isGroupChecked()"
                        :indeterminate="isGroupIndeterminate()"
                        @change="updateCheckedPermissions()"
                    />
                    <span class="pl-2 text-gray-600">{{ $t("admin.roles.form.no_group") }}</span>
                </div>

                <ul class="mb-2 ml-6">
                    <li v-for="permission in permissions.filter((x) => !x.group_id)" :key="permission.id">
                        <LabeledCheckbox
                            :value="permission.id"
                            :checked="form.permissions.includes(permission.id)"
                            :label="translate(permission.name)"
                            :description="translate(permission.description)"
                            name="permission"
                            @update-checked="updatePermission($event)"
                        ></LabeledCheckbox>
                    </li>
                </ul>
            </div>
        </fieldset>

        <FormAction :form="form" model="role" cancel-route="admin.role.index"></FormAction>
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import LabeledCheckbox from "@/Components/Admin/LabeledCheckbox.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";

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
    groups: {
        type: Array,
        default: () => [],
    },
    languages: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    auth: {
        type: Object,
        default: () => ({}),
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
    id: props.role.id ?? "",
    name: props.role.name ?? {},
    description: props.role.description ?? {},
    permissions: props.role.permissions?.map((permission) => permission.id) ?? [],
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const updatePermission = ({ value, checked }) => {
    form.permissions = form.permissions.filter((x) => x !== value);

    if (checked) {
        form.permissions.push(value);
    }
};

const isGroupChecked = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id == groupId) {
            if (!form.permissions.includes(permission.id)) {
                return false;
            }
        }
    }

    return true;
};

const isGroupUnchecked = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id == groupId) {
            if (form.permissions.includes(permission.id)) {
                return false;
            }
        }
    }

    return true;
};

const isGroupIndeterminate = (groupId) => {
    return !isGroupChecked(groupId) && !isGroupUnchecked(groupId);
};

const updateCheckedPermissions = (groupId) => {
    if (isGroupChecked(groupId)) {
        uncheckPermissionGroup(groupId);
    } else {
        checkPermissionGroup(groupId);
    }
};

const checkPermissionGroup = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id == groupId && !form.permissions.includes(permission.id)) {
            form.permissions.push(permission.id);
        }
    }
};

const uncheckPermissionGroup = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id == groupId) {
            form.permissions = form.permissions.filter((x) => x !== permission.id);
        }
    }
};
</script>

<style>
[type="checkbox"]:indeterminate {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}
</style>
