<template>
    <PageHead :title="$t('admin.users.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.index.title')" :description="$t('admin.users.index.description')" />

    <XModal />
    <CreateLink model="user"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <TableHeader
                        v-for="field in fields"
                        :key="field"
                        v-model:filter="filters[field]"
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.users.index.table.header.' + field)"
                        :is-sort-field="sortField === field"
                        is-filter-field
                        @sort="sortField = field"
                        @toggle-filter="toggleFilter(field)"
                    />
                    <TableHeader :label="$t('admin.general.table.actions')" :is-label-visible="false" />
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="user in paginator.data"
                    :key="user.id"
                    class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ user.name }}
                    </th>
                    <td class="px-6 py-4">
                        {{ user.email }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_system_user" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_admin" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_privileged" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_logged_in" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_banned" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ user.happenings_count }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <LinkGroup>
                            <PopupLink
                                v-if="hasPermission('edit_users')"
                                :action="user.is_banned ? 'unban' : 'ban'"
                                model="user"
                                :params="{ id: user.id }"
                            />
                            <ActionLink
                                v-if="hasPermission('edit_users')"
                                action="edit"
                                model="user"
                                :params="{ id: user.id }"
                            />
                            <PopupLink
                                v-if="hasPermission('delete_users')"
                                action="delete"
                                model="user"
                                :params="{ id: user.id }"
                            />
                        </LinkGroup>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex justify-center">
        <Paginator
            v-model:per-page="paginator.perPage"
            :current-page="paginator.currentPage"
            :last-page="paginator.lastPage"
            :next-page="paginator.nextPage.toString()"
            :prev-page="paginator.prevPage.toString()"
            @page-changed="paginator.jumpToPage($event)"
        />
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import Paginator from "@/Components/Admin/Paginator.vue";
import TableHeader from "@/Components/Admin/TableHeader.vue";
import { useSortFilterTable } from "@/Composables/SortFilterTable";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { ref, watch } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    users: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { hasPermission } = authStore;

const fields = [
    "name",
    "email",
    "is_system_user",
    "is_admin",
    "is_privileged",
    "is_logged_in",
    "is_banned",
    "happenings_count",
];

const users = ref(props.users);

const { filters, paginator, sortField, sortDirection, toggleFilter } = useSortFilterTable({
    data: users,
    initialSortField: "name",
    initialSortDirection: "asc",
    nonNumericFields: ["name", "email"],
});

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(
    () => props.users,
    () => {
        users.value = props.users;
    },
);
</script>
