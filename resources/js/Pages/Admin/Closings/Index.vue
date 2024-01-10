<template>
    <PageHead
        :title="
            $t('admin.closings.index.title', {
                type: $t('admin.closings.types.' + closable_type),
                title: translate(closable.title),
            })
        "
        page-type="admin"
    />
    <BodyHead
        :title="
            $t('admin.closings.index.title', {
                type: $t('admin.closings.types.' + closable_type),
                title: translate(closable.title),
            })
        "
        :description="$t('admin.closings.index.description')"
    />

    <PopupModal />
    <CreateLink model="closing" :params="{ closable_type: closable_type, closable_id: closable.id }"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.closings.index.table.header.start") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.closings.index.table.header.end") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.closings.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.closings.index.table.header.is_over") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="closing in closings"
                    :key="closing.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td class="px-6 py-4">
                        {{ getClosingDateTime(closing.start) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ getClosingDateTime(closing.end) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ translate(closing.description) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="isPastClosing(closing)" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink
                            v-if="hasPermission('edit_closings', institutionId)"
                            action="edit"
                            model="closing"
                            :params="{ id: closing.id }"
                        />
                        |
                        <DeleteLink
                            v-if="hasPermission('delete_closings', institutionId)"
                            action="delete"
                            model="closing"
                            :entity="closing"
                            :params="{
                                id: closing.id,
                                closable_id: closing.closable_id,
                                closable_type: closing.closable_type,
                            }"
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
import { computed } from "vue";

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
const translate = appStore.translate;
const { hasPermission } = authStore;
const institutionId = computed(() => {
    if (props.closable_type === "institution") {
        return props.closable.id;
    }

    return props.closable.institution_id;
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
