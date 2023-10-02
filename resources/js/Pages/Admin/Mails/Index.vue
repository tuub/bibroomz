<template>
    <PageHead :title="$t('admin.mails.index.title', { title: translate(institution.title) })" page-type="admin" />
    <BodyHead
        :title="$t('admin.mails.index.title', { title: translate(institution.title) })"
        :description="$t('admin.mails.index.description')"
    />

    <PopupModal />
    <CreateLink model="mail" :params="{ institution_id: institution.id }"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.mails.index.table.header.mail_type") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.mails.index.table.header.subject") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.mails.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="mail in mails"
                    :key="mail.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <th scope="row"
                        class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $t("admin.mails.mail_types." + mail.mail_type.key) }}
                    </th>
                    <td class="px-6 py-4 align-top">
                        {{ translate(mail.subject) }}
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <i v-if="mail.is_active" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!mail.is_active" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        <ActionLink v-if="hasPermission('edit_mails', mail.institution_id)"
                                    action="edit"
                                    model="mail"
                                    :params="{id: mail.id}" />
                        |
                        <DeleteLink v-if="hasPermission('delete_mails', mail.institution_id)"
                                    model="mail"
                                    :entity="mail"
                                    :params="{id: mail.id}" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import { useAppStore } from "@/Stores/AppStore";

import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";

import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    },
    mails: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(customParseFormat);

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
</script>
