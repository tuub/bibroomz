<template>
    <PageHead :title="$t('admin.roles.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.roles.index.title')" :description="$t('admin.roles.index.description')" />

    <XModal />
    <CreateLink model="role"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(role.name) }}
                    </td>
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
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
                        <ActionLink action="edit" model="role" :params="{ id: role.id }" />
                        |
                        <DeleteLink model="role" :entity="role" :params="{ id: role.id }" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";

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
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
</script>
