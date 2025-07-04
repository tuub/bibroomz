<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            ref="indexTable"
            v-model:filters="filters"
            :value="institutions"
            size="medium"
            striped-rows
            removable-sort
            table-style="min-width: 50rem"
            class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
            @row-reorder="reorderRows"
        >
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">{{ $t("admin.institutions.index.title") }}</div>
                        <div class="italic">{{ $t("admin.institutions.index.description") }}</div>
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
                        <CreateLink model="institution" />
                    </div>
                </div>
            </template>
            <template #empty>{{ $t("admin.general.table.no_records") }}</template>
            <template #loading>{{ $t("admin.general.table.loading_records") }}</template>
            <Column :row-reorder="!isSortedByColumn()" header-style="width: 3rem" />
            <Column field="title" :sortable="true" :header="$t('admin.institutions.index.table.header.title')">
                <template #body="slotProps">
                    {{ slotProps.data.title }}
                </template>
            </Column>
            <Column
                field="short_title"
                :sortable="true"
                :header="$t('admin.institutions.index.table.header.short_title')"
            ></Column>
            <Column field="slug" :sortable="true" :header="$t('admin.institutions.index.table.header.slug')"></Column>
            <Column
                field="location"
                :sortable="true"
                :header="$t('admin.institutions.index.table.header.location')"
            ></Column>
            <Column field="is_active" :sortable="true" :header="$t('admin.institutions.index.table.header.is_active')">
                <template #body="slotProps">
                    <BooleanField :is-true="slotProps.data.is_active" />
                </template>
            </Column>
            <Column :header="$t('admin.general.table.actions')">
                <template #body="slotProps">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_institution', slotProps.data.id)"
                            action="edit"
                            model="institution"
                            :params="{ id: slotProps.data.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('view_closings', slotProps.data.id)"
                            current="institution"
                            relation="closing"
                            :params="{ closable_type: 'institution', closable_id: slotProps.data.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('edit_institution', slotProps.data.id)"
                            current="institution"
                            relation="setting"
                            :params="{ settingable_type: 'institution', settingable_id: slotProps.data.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('view_mails', slotProps.data.id)"
                            current="institution"
                            relation="mail"
                            :params="{ institution_id: slotProps.data.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_institution', slotProps.data.id)"
                            action="delete"
                            model="institution"
                            :params="{ id: slotProps.data.id }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

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
import { inject, ref, watch } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    institutions: {
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
const indexTable = ref({});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(
    () => appStore.locale,
    () => {
        router.reload();
    },
);

const isSortedByColumn = () => {
    return !!indexTable.value.d_sortField;
};

const reorderRows = (event) => {
    const institutions = event.value;
    for (const [index, institution] of institutions.entries()) {
        institution.order = index + 1;
    }
    router.post(route("admin.institution.order"), institutions);
};
</script>
