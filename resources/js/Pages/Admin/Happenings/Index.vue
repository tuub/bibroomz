<template>
    <PageHead :title="$t('admin.happenings.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.happenings.index.title')" :description="$t('admin.happenings.index.description')" />

    <PopupModal />
    <CreateLink model="happening"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <TableHeader
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.happenings.index.table.header.date')"
                        :is-sort-field="sortField === 'date'"
                        @sort="sortField = 'date'"
                    />
                    <TableHeader
                        v-for="field in ['institution', 'resource_group', 'resource']"
                        :key="field"
                        v-model:filter="filters[field]"
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.happenings.index.table.header.' + field)"
                        :is-sort-field="sortField === field"
                        is-filter-field
                        @sort="sortField = field"
                        @toggle-filter="toggleFilter(field)"
                    />
                    <TableHeader
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.happenings.index.table.header.start_time')"
                        :is-sort-field="sortField === 'start'"
                        @sort="sortField = 'start'"
                    />
                    <TableHeader
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.happenings.index.table.header.end_time')"
                        :is-sort-field="sortField === 'end'"
                        @sort="sortField = 'end'"
                    />
                    <TableHeader
                        v-for="field in ['user1', 'user2', 'is_verified', 'is_over']"
                        :key="field"
                        v-model:filter="filters[field]"
                        v-model:sort-direction="sortDirection"
                        :label="$t('admin.happenings.index.table.header.' + field)"
                        :is-sort-field="sortField === field"
                        is-filter-field
                        @sort="sortField = field"
                        @toggle-filter="toggleFilter(field)"
                    />
                    <TableHeader :label="$t('admin.general.table.actions')" :is-label-visible="false" />
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="happening in paginator.data"
                    :key="happening.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ getHappeningDate(happening.date) }}
                    </th>
                    <td class="px-6 py-4">
                        {{ happening.institution }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.resource_group }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.resource }}
                    </td>
                    <td class="px-6 py-4">
                        {{ getHappeningTime(happening.start) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ getHappeningTime(happening.end) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.user1 }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.user2 ? happening.user2 : $t("admin.general.table.not_required") }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="happening.is_verified" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="happening.is_over" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink
                            v-if="hasPermission('edit_happenings', happening.institution_id)"
                            action="edit"
                            model="happening"
                            :params="{ id: happening.id }"
                        />
                        <span v-if="hasPermission('delete_happenings', happening.institution_id)">
                            |
                            <DeleteLink model="happening" :entity="happening" :params="{ id: happening.id }" />
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-2 flex justify-center">
        <Paginator
            v-model:per-page="paginator.perPage"
            :current-page="paginator.currentPage"
            :last-page="paginator.lastPage"
            :next-page="paginator.nextPage.toString()"
            :prev-page="paginator.prevPage.toString()"
            @page-changed="paginator.jumpToPage($event)"
        />
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";
import Paginator from "@/Components/Admin/Paginator.vue";
import TableHeader from "@/Components/Admin/TableHeader.vue";
import { useSortFilterTable } from "@/Composables/SortFilterTable";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { storeToRefs } from "pinia";
import { ref, watch } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happenings: {
        type: Object,
        required: true,
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const { locale } = storeToRefs(appStore);
const { formatDate, formatTime, translate } = appStore;

const { hasPermission } = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happenings = ref(mapHappenings(props.happenings));
const { filters, paginator, sortField, sortDirection, toggleFilter } = getPaginator(happenings);

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
        is_over: isPastHappening(happening),
    }));
}

function getPaginator(happenings) {
    return useSortFilterTable({
        data: happenings,
        initialSortField: "date",
        initialSortDirection: "asc",
        nonNumericFields: ["institution", "resource_group", "resource", "user1", "user2"],
    });
}

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(locale, () => {
    happenings.value = mapHappenings(props.happenings);
});
</script>
