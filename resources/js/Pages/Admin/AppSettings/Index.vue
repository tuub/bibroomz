<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";

import { transChoice } from "laravel-vue-i18n";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    settings: {
        type: Array,
        default: () => [],
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const indexTable = ref({});

const recordsCount = computed(() => {
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.settings.length;
});
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            :value="settings"
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
                        <div class="text-xl font-bold">{{ $t("admin.app_settings.index.title") }}</div>
                        <div class="italic">{{ $t("admin.app_settings.index.description") }}</div>
                    </div>
                    <ActionLink action="edit" model="app_setting" />
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>

            <Column field="label" :sortable="false" :header="$t('admin.app_settings.index.table.header.label')">
                <template #body="slotProps">
                    {{ $t("admin.app_settings.form.fields." + slotProps.data.key + ".label") }}
                </template>
            </Column>
            <Column field="key" :sortable="false" :header="$t('admin.app_settings.index.table.header.key')">
                <template #body="slotProps">
                    {{ slotProps.data.key }}
                </template>
            </Column>
            <Column field="value" :sortable="false" :header="$t('admin.app_settings.index.table.header.value')" />
        </DataTable>
    </div>
</template>
