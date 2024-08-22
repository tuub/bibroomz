<template>
    <IndexLayout
        model="happening"
        :title="$t('admin.happenings.index.title')"
        :description="$t('admin.happenings.index.description')"
    >
        <template #header>
            <IndexHeaderField
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === 'date'"
                @sort="sortField = 'date'"
            >
                {{ $t("admin.happenings.index.table.header.date") }}
            </IndexHeaderField>
            <IndexHeaderField
                v-for="field in ['institution', 'resource_group', 'resource']"
                :key="field"
                v-model:filter="filters[field]"
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === field"
                is-filter-field
                @sort="sortField = field"
                @toggle-filter="toggleFilter(field)"
            >
                {{ $t("admin.happenings.index.table.header." + field) }}
            </IndexHeaderField>
            <IndexHeaderField
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === 'start'"
                @sort="sortField = 'start'"
            >
                {{ $t("admin.happenings.index.table.header.start_time") }}
            </IndexHeaderField>
            <IndexHeaderField
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === 'end'"
                @sort="sortField = 'end'"
            >
                {{ $t("admin.happenings.index.table.header.end_time") }}
            </IndexHeaderField>
            <IndexHeaderField
                v-for="field in ['user1', 'user2', 'label', 'is_verified', 'is_over']"
                :key="field"
                v-model:filter="filters[field]"
                v-model:sort-direction="sortDirection"
                :is-sort-field="sortField === field"
                is-filter-field
                @sort="sortField = field"
                @toggle-filter="toggleFilter(field)"
            >
                {{ $t("admin.happenings.index.table.header." + field) }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="happening in paginator.data" :key="happening.id">
                <IndexRowHeaderField>
                    {{ getHappeningDate(happening.date) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ happening.institution }}
                </IndexRowField>
                <IndexRowField>
                    {{ happening.resource_group }}
                </IndexRowField>
                <IndexRowField>
                    {{ happening.resource }}
                </IndexRowField>
                <IndexRowField>
                    {{ getHappeningTime(happening.start) }}
                </IndexRowField>
                <IndexRowField>
                    {{ getHappeningTime(happening.end) }}
                </IndexRowField>
                <IndexRowField>
                    {{ happening.user1 }}
                </IndexRowField>
                <IndexRowField>
                    {{ happening.user2 ? happening.user2 : $t("admin.general.table.not_required") }}
                </IndexRowField>
                <IndexRowField>
                    {{ happening.label }}
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="happening.is_verified" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="happening.is_over" />
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_happenings', happening.institution_id)"
                            action="edit"
                            model="happening"
                            :params="{ id: happening.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_happenings', happening.institution_id)"
                            action="delete"
                            model="happening"
                            :params="{ id: happening.id }"
                        />
                    </LinkGroup>
                </IndexRowField>
            </IndexRow>
        </template>

        <template #footer>
            <Paginator
                v-model:per-page="paginator.perPage"
                :current-page="paginator.currentPage"
                :last-page="paginator.lastPage"
                :next-page="paginator.nextPage.toString()"
                :prev-page="paginator.prevPage.toString()"
                @page-changed="paginator.jumpToPage($event)"
            />
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import IndexRowHeaderField from "@/Components/Admin/IndexRowHeaderField.vue";
import Paginator from "@/Components/Admin/Paginator.vue";
import { useSortFilterTable } from "@/Composables/SortFilterTable";
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
        label: translate(happening.label),
        is_over: isPastHappening(happening),
    }));
}

function getPaginator(happenings) {
    return useSortFilterTable({
        data: happenings,
        initialSortField: "date",
        initialSortDirection: "asc",
        nonNumericFields: ["institution", "resource_group", "resource", "user1", "user2", "label"],
    });
}

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(locale, () => {
    happenings.value = mapHappenings(props.happenings);
});
</script>
