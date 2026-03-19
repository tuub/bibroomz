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
    closable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    closable_type: {
        type: String,
        default: "",
    },
    closings: {
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
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.closings.length;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getClosingDateTime = (dateTime) => {
    return [appStore.formatDate(dateTime, true), appStore.formatTime(dateTime, true)].join(" ");
};

const isPastClosing = (closing) => {
    return dayjs(closing.end).isBefore(dayjs().utcOffset(0, true));
};
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="closings"
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
                        <div class="text-xl font-bold">
                            {{
                                $t("admin.closings.index.title", {
                                    type: $t("admin.closings.types." + closable_type),
                                    title: translate(closable.title),
                                })
                            }}
                        </div>
                        <div class="italic">{{ $t("admin.closings.index.description") }}</div>
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
                        <CreateLink
                            model="closing"
                            :params="{ closable_type: closable_type, closable_id: closable.id }"
                        />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column field="start" :sortable="true" :header="$t('admin.closings.index.table.header.start')">
                <template #body="slotProps">
                    {{ getClosingDateTime(slotProps.data.start) }}
                </template>
            </Column>
            <Column field="end" :sortable="true" :header="$t('admin.closings.index.table.header.end')">
                <template #body="slotProps">
                    {{ getClosingDateTime(slotProps.data.end) }}
                </template>
            </Column>
            <Column
                :field="(c) => translate(c.description)"
                :sortable="true"
                :header="$t('admin.closings.index.table.header.description')"
            >
            </Column>
            <Column field="is_over" :sortable="true" :header="$t('admin.closings.index.table.header.is_over')">
                <template #body="slotProps">
                    <BooleanField :is-true="isPastClosing(slotProps.data)" />
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_closings')"
                            action="edit"
                            model="closing"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_closings')"
                            action="delete"
                            model="closing"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
