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
            required
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

            {{ form.permissions }}

            <div
                v-for="group in [...groups].sort((a, b) => translate(a.name).localeCompare(translate(b.name)))"
                :key="group.id"
            >
                <p>{{ translate(group.name) }}</p>

                <ul>
                    <li
                        v-for="(permission, index) in permissions.filter((x) => x.group_id === group.id)"
                        :key="permission.id"
                    >
                        <PermissionCheckbox
                            :permission="permission"
                            :index="index"
                            :checked="form.permissions.includes(permission.id)"
                            @update-checked="updatePermissions($event)"
                        ></PermissionCheckbox>
                    </li>
                </ul>
            </div>

            <div>
                <!-- Fixme -->
                <p>Others</p>

                <ul>
                    <li v-for="(permission, index) in permissions.filter((x) => !x.group_id)" :key="permission.id">
                        <PermissionCheckbox
                            :permission="permission"
                            :index="index"
                            :checked="form.permissions.includes(permission.id)"
                            @update-checked="updatePermissions($event)"
                        ></PermissionCheckbox>
                    </li>
                </ul>
            </div>
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
import PermissionCheckbox from "@/Components/Admin/PermissionCheckbox.vue";
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
    groups: {
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

const updatePermissions = ({ permissionId, checked }) => {
    form.permissions = form.permissions.filter((x) => x !== permissionId);

    if (checked) {
        form.permissions.push(permissionId);
    }
};
</script>
