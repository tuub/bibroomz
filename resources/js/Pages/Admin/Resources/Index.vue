<template>
    <PageHead :title="$t('admin.resources.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.resources.index.title')" :description="$t('admin.resources.index.description')" />

    <PopupModal />
    <CreateLink model="resource" :params="{ resource_group_id: resourceGroup.id }"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.location") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.business_hours") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.capacity") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.is_verification_required") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="resource in resources"
                    :key="resource.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white"
                    >
                        {{ translate(resource.title) }}
                    </th>
                    <td class="px-6 py-4 align-top">
                        {{ translate(resource.location) }}
                        <template v-if="resource.location_uri">
                            <a :href="resource.location_uri" target="_blank">
                                <i class="inline ri-external-link-line"></i>
                            </a>
                        </template>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <p v-for="business_hour in resource.business_hours" :key="business_hour.id">
                            {{
                                getBusinessHourTime(business_hour.start) + "-" + getBusinessHourTime(business_hour.end)
                            }}
                            ({{
                                business_hour.week_days
                                    .map((week_day) =>
                                        trans("admin.general.week_days." + week_day.key + ".short_label"),
                                    )
                                    .join(", ")
                            }})
                        </p>
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        {{ resource.capacity }}
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <BooleanField :is-true="resource.is_active" />
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <BooleanField :is-true="resource.is_verification_required" />
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        <!-- FIXME: resource.resource_group.institution.id? -->
                        <ActionLink
                            v-if="hasPermission('edit_resources', resource.institution_id)"
                            action="edit"
                            model="resource"
                            :params="{ id: resource.id }"
                        />
                        |
                        <ActionLink
                            v-if="hasPermission('create_resources', resource.institution_id)"
                            action="clone"
                            model="resource"
                            method="post"
                            as="button"
                            :params="{ id: resource.id }"
                        />
                        |
                        <RelationLink
                            v-if="hasPermission('view_closings', resource.institution_id)"
                            current="resource"
                            relation="closing"
                            :params="{ closable_type: 'resource', closable_id: resource.id }"
                        />
                        |
                        <DeleteLink
                            v-if="hasPermission('delete_resources', resource.institution_id)"
                            model="resource"
                            :entity="resource"
                            :params="{ id: resource.id }"
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
import RelationLink from "@/Components/Admin/Index/RelationLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import { trans } from "laravel-vue-i18n";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    resources: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
// FIXME: Why do we still need this?
dayjs.extend(customParseFormat);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getBusinessHourTime = (datetime) => {
    return appStore.formatTime(datetime, false, "HH:mm:ss");
};

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
