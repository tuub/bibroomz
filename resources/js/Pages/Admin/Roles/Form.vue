<template>
    <PageHead :title="$t('admin.roles.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.roles.form.title')" :description="$t('admin.roles.form.description')" />

    <form class="max-w mx-auto mt-8">
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

            <div
                v-for="group in [...groups].sort((a, b) => translate(a.name).localeCompare(translate(b.name)))"
                :key="group.id"
            >
                <div>
                    <input
                        :id="`group-checkbox-${group.id}`"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        type="checkbox"
                        :checked="isGroupChecked(group.id)"
                        :indeterminate="isGroupIndeterminate(group.id)"
                        @change="updateCheckedPermissions(group.id)"
                    />
                    <span class="pl-2 text-gray-600">{{ translate(group.name) }}</span>
                </div>

                <ul class="ml-6 mb-2">
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
                <!-- Fixme -->
                <div>
                    <input
                        id="no-group-checkbox"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        type="checkbox"
                        :checked="isGroupChecked()"
                        :indeterminate="isGroupIndeterminate()"
                        @change="updateCheckedPermissions()"
                    />
                    <span class="pl-2 text-gray-600">Other Permissions</span>
                </div>

                <ul class="ml-6 mb-2">
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
        </div>

        <FormAction :form="form" model="role" cancel-route="admin.role.index"></FormAction>
    </form>
</template>
<script setup>
import LabeledCheckbox from "@/Components/Admin/LabeledCheckbox.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormAction from "@/Components/Admin/FormAction.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import PageHead from "@/Shared/PageHead.vue";
import {useAppStore} from "@/Stores/AppStore";
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
        if (permission.group_id === groupId) {
            if (!form.permissions.includes(permission.id)) {
                return false;
            }
        }
    }

    return true;
};

const isGroupUnchecked = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id === groupId) {
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
        if (permission.group_id === groupId && !form.permissions.includes(permission.id)) {
            form.permissions.push(permission.id);
        }
    }
};

const uncheckPermissionGroup = (groupId) => {
    for (const permission of props.permissions) {
        if (permission.group_id === groupId) {
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
