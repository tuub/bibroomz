<template>
    <PageHead :title="$t('admin.users.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.index.title')" :description="$t('admin.users.index.description')" />

    <XModal />
    <CreateLink model="user"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
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
                        <ActionLink
                            :action="user.is_banned ? 'unban' : 'ban'"
                            model="user"
                            method="post"
                            :params="{ id: user.id }"
                        />
                        |
                        <ActionLink action="edit" model="user" :params="{ id: user.id }" />
                        |
                        <DeleteLink model="user" :entity="user" :params="{ id: user.id }" />
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
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";
import Paginator from "@/Components/Admin/Paginator.vue";
import TableHeader from "@/Components/Admin/TableHeader.vue";
import { useSortFilterTable } from "@/Composables/SortFilterTable";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";

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
// Variables
// ------------------------------------------------
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

watch(
    () => props.users,
    () => {
        users.value = props.users;
    },
);
</script>
