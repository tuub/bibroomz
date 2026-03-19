<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
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
    happenings: {
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
const { formatDate, formatTime, translate } = appStore;
const indexTable = ref({});

const happenings = ref(mapHappenings(props.happenings));

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recordsCount = computed(() => {
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.happenings.length;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
function getHappeningDate(datetime) {
    return formatDate(datetime, true);
}

function getHappeningTime(datetime) {
    return formatTime(datetime, true);
}

function isPastHappening(happening) {
    return dayjs(happening.end).isBefore(dayjs().utcOffset(0, true));
}

function mapHappenings(happenings) {
    return happenings.map((happening) => ({
        ...happening,
        date: dayjs(happening.start),
        start: dayjs(happening.start),
        end: dayjs(happening.end),
        institution: translate(happening.institution),
        resource_group: translate(happening.resource_group),
        resource: translate(happening.resource),
        label: translate(happening.label),
        is_over: isPastHappening(happening),
    }));
}
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="happenings"
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
                        <div class="text-xl font-bold">{{ $t("admin.happenings.index.title") }}</div>
                        <div class="italic">{{ $t("admin.happenings.index.description") }}</div>
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
                        <CreateLink model="happening" />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column field="date" :sortable="true" :header="$t('admin.happenings.index.table.header.date')">
                <template #body="slotProps">
                    {{ getHappeningDate(slotProps.data.date) }}
                </template>
            </Column>
            <Column
                field="institution"
                :sortable="true"
                :header="$t('admin.happenings.index.table.header.institution')"
            />
            <Column
                field="resource_group"
                :sortable="true"
                :header="$t('admin.happenings.index.table.header.resource_group')"
            />
            <Column field="resource" :sortable="true" :header="$t('admin.happenings.index.table.header.resource')" />
            <Column field="start" :sortable="true" :header="$t('admin.happenings.index.table.header.start_time')">
                <template #body="slotProps">
                    {{ getHappeningTime(slotProps.data.start) }}
                </template>
            </Column>
            <Column field="end" :sortable="true" :header="$t('admin.happenings.index.table.header.end_time')">
                <template #body="slotProps">
                    {{ getHappeningTime(slotProps.data.end) }}
                </template>
            </Column>
            <Column field="user1" :sortable="true" :header="$t('admin.happenings.index.table.header.user1')" />
            <Column field="user2" :sortable="true" :header="$t('admin.happenings.index.table.header.user2')" />
            <Column field="label" :sortable="true" :header="$t('admin.happenings.index.table.header.label')" />
            <Column
                field="is_verified"
                :sortable="true"
                :header="$t('admin.happenings.index.table.header.is_verified')"
            >
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_verified" />
                </template>
            </Column>
            <Column field="is_over" :sortable="true" :header="$t('admin.happenings.index.table.header.is_over')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_over" />
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_happening', slotProps.data.id)"
                            action="edit"
                            model="happening"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_happening', slotProps.data.id)"
                            action="delete"
                            model="happening"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
