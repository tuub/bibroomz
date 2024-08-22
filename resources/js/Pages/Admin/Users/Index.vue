<template>
    <IndexLayout model="user" :title="$t('admin.users.index.title')" :description="$t('admin.users.index.description')">
        <template #header>
            <IndexHeaderField
                v-for="field in fields"
                :key="field"
                v-model:filter="filters[field]"
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === field"
                is-filter-field
                @sort="sortField = field"
                @toggle-filter="toggleFilter(field)"
            >
                {{ $t("admin.users.index.table.header." + field) }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="user in paginator.data" :key="user.id">
                <IndexRowHeaderField>
                    {{ user.name }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ user.email }}
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="user.is_system_user" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="user.is_admin" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="user.is_privileged" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="user.is_logged_in" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="user.is_banned" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    {{ user.happenings_count }}
                </IndexRowField>
                <IndexRowField class="text-right">
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
                </IndexRowField>
            </IndexRow>
        </template>

        <template #footer>
            <Paginator
                v-model:per-page="paginator.perPage"
                :current-page="paginator.currentPage"
                :last-page="paginator.lastPage"
                :next-page="paginator.nextPage.toString()"
                :prev-page="paginator.prevPage.toString()"
                @page-changed="paginator.jumpToPage($event)"
            />
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import IndexRowHeaderField from "@/Components/Admin/IndexRowHeaderField.vue";
import Paginator from "@/Components/Admin/Paginator.vue";
import { useSortFilterTable } from "@/Composables/SortFilterTable";
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
