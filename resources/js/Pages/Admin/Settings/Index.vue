<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { transChoice } from "laravel-vue-i18n";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    settingable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    settingable_type: {
        type: String,
        default: "",
    },
    settings: {
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
const { translate } = appStore;
const indexTable = ref({});

const recordsCount = computed(() => {
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.settings.length;
});

const institutionId = computed(() => {
    if (props.settingable_type === "institution") {
        return props.settingable.id;
    }

    return props.settingable.institution_id;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
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
                        <div class="text-xl font-bold">
                            {{
                                $t("admin.settings.index.title", {
                                    type: $t("admin.settings.types." + settingable_type),
                                    title: translate(settingable.title),
                                })
                            }}
                        </div>
                        <div class="italic">{{ $t("admin.settings.index.description") }}</div>
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>

            <Column field="label" :sortable="false" :header="$t('admin.settings.index.table.header.label')">
                <template #body="slotProps">
                    {{ $t("admin.settings.keys." + slotProps.data.key + ".label") }}
                </template>
            </Column>

            <Column field="key" :sortable="false" :header="$t('admin.settings.index.table.header.key')">
                <template #body="slotProps">
                    {{ slotProps.data.key }}
                </template>
            </Column>
            <Column field="value" :sortable="false" :header="$t('admin.settings.index.table.header.value')" />
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_settings', institutionId)"
                            action="edit"
                            model="setting"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
