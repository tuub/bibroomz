<template>
    <PageHead :title="$t('admin.institutions.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.institutions.index.title')" :description="$t('admin.institutions.index.description')" />

    <PopupModal />
    <CreateLink model="institution"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.short_title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.slug") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.location") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.institutions.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="institution in institutions"
                    :key="institution.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(institution.title) }}
                    </th>
                    <td class="px-6 py-4">
                        {{ institution.short_title }}
                    </td>
                    <td class="px-6 py-4">
                        {{ institution.slug }}
                    </td>
                    <td class="px-6 py-4">
                        {{ institution.location }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="institution.is_active" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!institution.is_active" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink v-if="hasPermission('edit_institution', institution.id)"
                                     action="edit"
                                     model="institution"
                                     :params="{id: institution.id}" />
                        |
                        <ActionLink v-if="hasPermission('view_closings', institution.id)"
                                    action="index"
                                    model="closing"
                                    :params="{closable_type: 'institution',closable_id: institution.id}" />
                        |
                        <ActionLink v-if="hasPermission('edit_institution', institution.id)"
                                    action="index"
                                    model="setting"
                                    :params="{institution_id: institution.id}" />
                        |
                        <ActionLink v-if="hasPermission('view_mails', institution.id)"
                                    action="index"
                                    model="mail"
                                    :params="{institution_id: institution.id}" />
                        |
                        <DeleteLink v-if="hasPermission('delete_institution', institution.id)"
                                    model="institution"
                                    :entity="institution"
                                    :params="{id: institution.id}" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";

import { router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import { Modal as FlowbiteModal } from "flowbite";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount, onMounted } from "vue";

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
