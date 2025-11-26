<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import RelationLink from "@/Components/Admin/Index/RelationLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { FilterMatchMode } from "@primevue/core/api";
import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    resourceGroup: {
        type: Object,
        default: () => ({}),
    },
    resources: {
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
const { hasPermission } = authStore;
const { formatDate, formatTime, translate } = appStore;

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getBusinessHourTime = (datetime) => {
    return formatTime(datetime, false, "HH:mm:ss");
};

const formatBusinessHourDates = (startDate, endDate) => {
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
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            v-model:filters="filters"
            :value="resources"
            size="medium"
            striped-rows
            show-gridlines
            removable-sort
            table-style="min-width: 50rem"
            class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
        >
            <!-- HEADER -->
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">
                            {{ translate(resourceGroup.institution.title) }}
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
                        <CreateLink model="resource" />
                    </div>
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>

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
                        <RelationLink
                            v-if="hasPermission('edit_resource_group', slotProps.data.id)"
                            current="resource_group"
                            relation="setting"
                            :params="{ settingable_type: 'resource_group', settingable_id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
