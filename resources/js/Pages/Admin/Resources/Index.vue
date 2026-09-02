<script setup lang="ts">
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import RelationLink from "@/Components/Admin/Index/RelationLink.vue";
import { postReorderedRows } from "@/Composables/Reorder";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { AdminResource, DataTableRef, ResourceGroup } from "@/Types/Admin";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { FilterMatchMode } from "@primevue/core/api";
import { transChoice } from "laravel-vue-i18n";
import { computed, inject, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        resourceGroup?: ResourceGroup;
        resources?: AdminResource[];
    }>(),
    {
        resourceGroup: () => ({}),
        resources: () => [],
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const route = inject<ZiggyRouteFn>("ziggyRoute")!;
const hasPermission = (ability: string, institutionId?: string | number) =>
    authStore.hasPermission(ability, institutionId);
const { formatDate, formatTime, translate } = appStore;
const indexTable = ref<DataTableRef>(null);
const institution = props.resourceGroup.institution;
if (!institution) {
    throw new Error("Resources index requires a resource group with an institution.");
}
const routeParams = {
    resource_group_id: props.resourceGroup.id,
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recordsCount = computed(() => {
    return indexTable.value?.processedData ? indexTable.value.processedData.length : props.resources.length;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getBusinessHourTime = (datetime?: string) => {
    return formatTime(datetime, false, "HH:mm:ss");
};

const formatBusinessHourDates = (startDate?: string | null, endDate?: string | null) => {
    let formatString = "";

    if (startDate) {
        formatString += formatDate(startDate);
    }

    if (startDate || endDate) {
        formatString += "-";
    }

    if (endDate) {
        formatString += formatDate(endDate);
    }

    if (startDate || endDate) {
        formatString += ":";
    }

    return formatString;
};

const isSortedByColumn = () => {
    return !!indexTable.value?.d_sortField;
};

const reorderRows = (event: { value: AdminResource[] }) => {
    postReorderedRows(route("admin.resource.order"), event.value);
};
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="resources"
            size="medium"
            striped-rows
            show-gridlines
            removable-sort
            table-style="min-width: 50rem"
            class="text-app-muted dark:text-app-subtle w-full text-left text-sm"
            @row-reorder="reorderRows"
        >
            <!-- HEADER -->
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">
                            {{ translate(institution.title) }}
                            <i class="pi pi-angle-double-right"></i>
                            {{ translate(resourceGroup.title) }}
                            <i class="pi pi-angle-double-right"></i>
                            {{ $t("admin.resources.index.title") }}
                        </div>
                        <div class="italic">{{ $t("admin.resources.index.description") }}</div>
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
                        <CreateLink model="resource" :params="routeParams" />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: String(recordsCount) }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column :row-reorder="!isSortedByColumn()" header-style="width: 3rem" />
            <!-- DATA COLUMNS -->
            <Column
                :field="(r) => translate(r.title)"
                :sortable="true"
                :header="$t('admin.resources.index.table.header.title')"
            />
            <Column
                :field="(r) => translate(r.location)"
                :sortable="true"
                :header="$t('admin.resources.index.table.header.location')"
            />
            <Column
                field="business_hours"
                :sortable="true"
                :header="$t('admin.resources.index.table.header.business_hours')"
            >
                <template #body="slotProps">
                    <ul>
                        <li v-for="business_hour in slotProps.data.business_hours" :key="business_hour.id">
                            {{ formatBusinessHourDates(business_hour.start_date, business_hour.end_date) }}
                            {{
                                getBusinessHourTime(business_hour.start) +
                                " - " +
                                getBusinessHourTime(business_hour.end)
                            }}
                        </li>
                    </ul>
                </template>
            </Column>
            <Column field="capacity" :sortable="true" :header="$t('admin.resources.index.table.header.capacity')" />
            <Column field="is_active" :sortable="true" :header="$t('admin.institutions.index.table.header.is_active')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_active" />
                </template>
            </Column>

            <!-- ACTION COLUMNS -->
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_resource', slotProps.data.id)"
                            action="edit"
                            model="resource"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('create_resources', slotProps.data.institution_id)"
                            action="clone"
                            model="resource"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_resource', slotProps.data.id)"
                            action="delete"
                            model="resource"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
            <Column :header="$t('admin.general.table.relations')">
                <template #body="slotProps">
                    <LinkGroup>
                        <RelationLink
                            v-if="hasPermission('view_closings', slotProps.data.id)"
                            current="resource"
                            relation="closing"
                            :params="{ closable_type: 'resource', closable_id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
