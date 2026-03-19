<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import RelationLink from "@/Components/Admin/Index/RelationLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { router } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { transChoice } from "laravel-vue-i18n";
import { computed, inject, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    resource_groups: {
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
const route = inject("route");
const { hasPermission } = authStore;
const { translate } = appStore;
const indexTable = ref({});
const routeParams = {
    institution_id: props.institution.id,
};

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const recordsCount = computed(() => {
    return indexTable.value.processedData ? indexTable.value.processedData.length : props.resource_groups.length;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const isSortedByColumn = () => {
    return !!indexTable.value.d_sortField;
};

const reorderRows = (event) => {
    const resource_groups = event.value;
    for (const [index, resource_group] of resource_groups.entries()) {
        resource_group.order = index + 1;
    }
    router.post(route("admin.resource_group.order"), resource_groups);
};
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="resource_groups"
            size="medium"
            striped-rows
            show-gridlines
            removable-sort
            table-style="min-width: 50rem"
            class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
            @row-reorder="reorderRows"
        >
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">
                            {{ translate(institution.title) }}
                            <i class="pi pi-angle-double-right"></i>
                            {{ $t("admin.resource_groups.index.title") }}
                        </div>
                        <div class="italic">{{ $t("admin.resource_groups.index.description") }}</div>
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
                        <CreateLink model="resource_group" :params="routeParams" />
                    </div>
                </div>
                <div class="mt-2 text-right text-xs">
                    {{ transChoice("admin.general.records_count", recordsCount, { count: recordsCount }) }}
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column :row-reorder="!isSortedByColumn()" header-style="width: 3rem" />
            <Column
                :field="(r) => translate(r.title)"
                :sortable="true"
                :header="$t('admin.resource_groups.index.table.header.title')"
            />
            <Column
                :field="(r) => translate(r.description)"
                :sortable="true"
                :header="$t('admin.resource_groups.index.table.header.description')"
            />
            <Column
                field="resources_count"
                :sortable="true"
                :header="$t('admin.institutions.index.table.header.resources_count')"
            />
            <Column field="is_active" :sortable="true" :header="$t('admin.institutions.index.table.header.is_active')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_active" />
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_resource_group', slotProps.data.id)"
                            action="edit"
                            model="resource_group"
                            :params="{ id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_resource_group', slotProps.data.id)"
                            action="delete"
                            model="resource_group"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
            <Column :header="$t('admin.general.table.relations')">
                <template #body="slotProps">
                    <LinkGroup>
                        <RelationLink
                            v-if="hasPermission('view_resources', slotProps.data.id)"
                            current="resource_group"
                            relation="resource"
                            :params="{ resource_group_id: slotProps.data.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('edit_resource_group', slotProps.data.id)"
                            inject
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
