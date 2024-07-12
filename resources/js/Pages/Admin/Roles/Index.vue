<template>
    <PageHead :title="$t('admin.roles.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.roles.index.title')" :description="$t('admin.roles.index.description')" />

    <XModal />
    <CreateLink model="role"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.name") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.permissions") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="role in roles"
                    :key="role.id"
                    class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600"
                >
                    <td scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ translate(role.name) }}
                    </td>
                    <td scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ translate(role.description) }}
                    </td>
                    <td class="px-6 py-4 text-left">
                        {{
                            role.permissions
                                .sort((a, b) => translate(a.name).localeCompare(translate(b.name)))
                                .map((permission) => translate(permission.name))
                                .join(", ")
                        }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <LinkGroup>
                            <ActionLink
                                v-if="hasPermission('edit_roles')"
                                action="edit"
                                model="role"
                                :params="{ id: role.id }"
                            />
                            <PopupLink
                                v-if="hasPermission('delete_roles')"
                                action="delete"
                                model="role"
                                :entity="role"
                                :params="{ id: role.id }"
                            />
                        </LinkGroup>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    roles: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
