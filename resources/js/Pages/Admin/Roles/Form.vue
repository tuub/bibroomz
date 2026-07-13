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
                    <li v-for="permission in getPermissionsByGroup(group.id)" :key="permission.id">
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
                    <li v-for="permission in getUngroupedPermissions()" :key="permission.id">
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
<script setup lang="ts">
import FormAction from "@/Components/Admin/FormAction.vue";
import LabeledCheckbox from "@/Components/Admin/LabeledCheckbox.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { LabeledCheckboxUpdatePayload, Permission, PermissionGroup, Role } from "@/Types/Admin";

import { useForm } from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        role?: Role;
        permissions?: Permission[];
        groups?: PermissionGroup[];
        languages: string[];
        errors?: Record<string, unknown>;
        auth?: Record<string, unknown>;
    }>(),
    {
        role: () => ({}),
        permissions: () => [],
        groups: () => [],
        errors: () => ({}),
        auth: () => ({}),
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
type PermissionWithId = Permission & { id: number | string };

const hasPermissionId = (permission: Permission): permission is PermissionWithId => permission.id != null;
const getPermissionsByGroup = (groupId?: number | string) =>
    props.permissions.filter(
        (permission): permission is PermissionWithId => hasPermissionId(permission) && permission.group_id == groupId,
    );
const getUngroupedPermissions = () =>
    props.permissions.filter(
        (permission): permission is PermissionWithId => hasPermissionId(permission) && !permission.group_id,
    );

const form = useForm({
    id: props.role.id ?? "",
    name: props.role.name ?? {},
    description: props.role.description ?? {},
    permissions: props.role.permissions?.filter(hasPermissionId).map((permission) => permission.id) ?? [],
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const updatePermission = ({ value, checked }: LabeledCheckboxUpdatePayload) => {
    form.permissions = form.permissions.filter((x) => x !== value);

    if (checked) {
        form.permissions.push(value);
    }
};

const isGroupChecked = (groupId?: number | string) => {
    for (const permission of props.permissions) {
        if (permission.id == null) {
            continue;
        }

        if (permission.group_id == groupId) {
            if (!form.permissions.includes(permission.id)) {
                return false;
            }
        }
    }

    return true;
};

const isGroupUnchecked = (groupId?: number | string) => {
    for (const permission of props.permissions) {
        if (permission.id == null) {
            continue;
        }

        if (permission.group_id == groupId) {
            if (form.permissions.includes(permission.id)) {
                return false;
            }
        }
    }

    return true;
};

const isGroupIndeterminate = (groupId?: number | string) => {
    return !isGroupChecked(groupId) && !isGroupUnchecked(groupId);
};

const updateCheckedPermissions = (groupId?: number | string) => {
    if (isGroupChecked(groupId)) {
        uncheckPermissionGroup(groupId);
    } else {
        checkPermissionGroup(groupId);
    }
};

const checkPermissionGroup = (groupId?: number | string) => {
    for (const permission of props.permissions) {
        if (permission.id == null) {
            continue;
        }

        if (permission.group_id == groupId && !form.permissions.includes(permission.id)) {
            form.permissions.push(permission.id);
        }
    }
};

const uncheckPermissionGroup = (groupId?: number | string) => {
    for (const permission of props.permissions) {
        if (permission.id == null) {
            continue;
        }

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
