<template>
    <PageHead :title="$t('admin.happenings.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.happenings.index.title')" :description="$t('admin.happenings.index.description')" />

    <PopupModal />
    <CreateLink model="happening"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.date") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.institution") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.resource_group") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.resource") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.start_time") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.end_time") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.user_01") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.user_02") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.happenings.index.table.header.is_verified") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.happenings.index.table.header.is_over") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="happening in happenings"
                    :key="happening.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ getHappeningDate(happening.start) }}
                    </th>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.resource_group.institution.title) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.resource_group.name) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.title) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ getHappeningTime(happening.start) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ getHappeningTime(happening.end) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.user1.name }}
                    </td>
                    <td class="px-6 py-4">
                        <span v-if="happening.is_verified && happening.user2">
                            {{ happening.user2.name }}
                        </span>
                        <span v-else-if="happening.verifier">
                            {{ happening.verifier }}
                        </span>
                        <span v-else>
                            {{ $t("admin.general.table.not_required") }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="happening.is_verified" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="isPastHappening(happening)" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink
                            v-if="hasPermission('edit_happenings', happening.resource.institution_id)"
                            action="edit"
                            model="happening"
                            :params="{ id: happening.id }"
                        />
                        |
                        <DeleteLink
                            v-if="hasPermission('delete_happenings', happening.resource.institution_id)"
                            model="happening"
                            :entity="happening"
                            :params="{ id: happening.id }"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
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
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getHappeningDate = (datetime) => {
    return appStore.formatDate(datetime, true);
};

const getHappeningTime = (datetime) => {
    return appStore.formatTime(datetime, true);
};

const isPastHappening = (happening) => {
    return dayjs(happening.end).isBefore(dayjs().utcOffset(0, true));
};

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
