<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { FilterMatchMode } from "@primevue/core/api";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { transChoice } from "laravel-vue-i18n";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    roles: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { hasPermission } = authStore;
const { translate } = appStore;
const indexTable = ref({});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recordsCount = computed(() => {
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.roles.length;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="roles"
            size="medium"
            striped-rows
            show-gridlines
            removable-sort
            table-style="min-width: 50rem"
            class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
        >
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">{{ $t("admin.roles.index.title") }}</div>
                        <div class="italic">{{ $t("admin.roles.index.description") }}</div>
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
                        <CreateLink model="role" />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column
                :field="(r) => translate(r.name)"
                :sortable="true"
                :header="$t('admin.roles.index.table.header.name')"
            />
            <Column
                :field="(u) => translate(u.description)"
                :sortable="true"
                :header="$t('admin.roles.index.table.header.description')"
            />
            <Column :field="permissions" :sortable="true" :header="$t('admin.roles.index.table.header.description')">
                <template #body="slotProps">
                    {{
                        slotProps.data.permissions
                            .sort((a, b) => translate(a.name).localeCompare(translate(b.name)))
                            .map((permission) => translate(permission.name))
                            .join(", ")
                    }}
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_role', slotProps.data.id)"
                            action="edit"
                            model="role"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_role', slotProps.data.id)"
                            action="delete"
                            model="role"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
