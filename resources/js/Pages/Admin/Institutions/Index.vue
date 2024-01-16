<template>
    <PageHead :title="$t('admin.institutions.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.institutions.index.title')" :description="$t('admin.institutions.index.description')" />

    <XModal />
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
                        <BooleanField :is-true="institution.is_active" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink
                            v-if="hasPermission('edit_institution', institution.id)"
                            action="edit"
                            model="institution"
                            :params="{ id: institution.id }"
                        />
                        |
                        <RelationLink
                            v-if="hasPermission('view_closings', institution.id)"
                            current="institution"
                            relation="closing"
                            :params="{ closable_type: 'institution', closable_id: institution.id }"
                        />
                        |
                        <RelationLink
                            v-if="hasPermission('edit_institution', institution.id)"
                            current="institution"
                            relation="setting"
                            :params="{ settingable_type: 'institution', settingable_id: institution.id }"
                        />
                        |
                        <RelationLink
                            v-if="hasPermission('view_mails', institution.id)"
                            current="institution"
                            relation="mail"
                            :params="{ institution_id: institution.id }"
                        />
                        |
                        <DeleteLink
                            v-if="hasPermission('delete_institution', institution.id)"
                            model="institution"
                            :entity="institution"
                            :params="{ id: institution.id }"
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
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

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
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
