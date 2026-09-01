<script setup lang="ts">
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import ImpersonateLink from "@/Components/Admin/Index/ImpersonateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { AdminUser, DataTableRef } from "@/Types/Admin";

import { FilterMatchMode } from "@primevue/core/api";
import { transChoice } from "laravel-vue-i18n";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        users?: AdminUser[];
    }>(),
    {
        users: () => [],
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { translate } = appStore;
const { hasPermission } = authStore;
const indexTable = ref<DataTableRef>(null);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recordsCount = computed(() => {
    return indexTable.value?.processedData ? indexTable.value.processedData.length : props.users.length;
});

const translateUserGroups = (userGroups: AdminUser["user_groups"] = []) => {
    return userGroups.map((userGroup) => translate(userGroup.title)).join(", ");
};
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="users"
            size="medium"
            striped-rows
            show-gridlines
            removable-sort
            paginator
            :rows="50"
            :rows-per-page-options="[25, 50, 100]"
            table-style="min-width: 50rem"
            class="w-full text-left text-sm text-app-muted dark:text-app-subtle"
        >
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">{{ $t("admin.users.index.title") }}</div>
                        <div class="italic">{{ $t("admin.users.index.description") }}</div>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                v-model="filters['global'].value"
                                :placeholder="$t('admin.general.table.keyword_search')"
                            />
                        </IconField>
                        <CreateLink model="user" />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: String(recordsCount) }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column field="name" :sortable="true" :header="$t('admin.users.index.table.header.name')" />
            <Column field="email" :sortable="true" :header="$t('admin.users.index.table.header.email')">
                <template #body="slotProps">
                    <a :href="'mailto:' + slotProps.data.email" class="text-link hover:underline">
                        {{ slotProps.data.email }}
                    </a>
                </template>
            </Column>
            <Column
                field="is_system_user"
                :sortable="true"
                :header="$t('admin.users.index.table.header.is_system_user')"
            >
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_system_user" />
                </template>
            </Column>
            <Column field="is_admin" :sortable="true" :header="$t('admin.users.index.table.header.is_admin')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_admin" />
                </template>
            </Column>
            <Column field="is_privileged" :sortable="true" :header="$t('admin.users.index.table.header.is_privileged')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_privileged" />
                </template>
            </Column>
            <Column field="is_logged_in" :sortable="true" :header="$t('admin.users.index.table.header.is_logged_in')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_logged_in" />
                </template>
            </Column>
            <Column field="is_banned" :sortable="true" :header="$t('admin.users.index.table.header.is_banned')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_banned" />
                </template>
            </Column>
            <Column
                field="happenings_count"
                :sortable="true"
                :header="$t('admin.users.index.table.header.happenings_count')"
            />
            <Column :header="$t('admin.users.index.table.header.user_groups')">
                <template #body="slotProps">
                    {{ translateUserGroups(slotProps.data.user_groups) }}
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ImpersonateLink
                            v-if="authStore.isAdmin && slotProps.data.id !== authStore.user?.id"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('edit_users')"
                            :action="slotProps.data.is_banned ? 'unban' : 'ban'"
                            model="user"
                            :params="{ id: slotProps.data.id }"
                        />
                        <ActionLink
                            v-if="hasPermission('edit_users')"
                            action="edit"
                            model="user"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_users')"
                            action="delete"
                            model="user"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
