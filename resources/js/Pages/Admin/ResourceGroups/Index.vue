<template>
    <PageHead :title="$t('admin.resource_groups.index.title')" page-type="admin" />
    <BodyHead
        :title="$t('admin.resource_groups.index.title')"
        :description="$t('admin.resource_groups.index.description')"
    />

    <XModal />
    <CreateLink model="resource_group"></CreateLink>

    <div class="relative overflow-x-auto shadow-md">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resource_groups.index.table.header.title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resource_groups.index.table.header.slug") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resource_groups.index.table.header.institution") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resource_groups.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resource_groups.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="resource_group in resource_groups"
                    :key="resource_group.id"
                    class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="whitespace-nowrap px-6 py-4 align-top font-medium text-gray-900 dark:text-white"
                    >
                        {{ translate(resource_group.title) }}
                    </th>
                    <td class="px-6 py-4 align-top">
                        {{ resource_group.slug }}
                    </td>
                    <td class="px-6 py-4 align-top">
                        {{ translate(resource_group.institution.title) }}
                    </td>
                    <td class="px-6 py-4 align-top">
                        {{ translate(resource_group.description) }}
                    </td>
                    <td class="px-6 py-4 text-center align-top">
                        <BooleanField :is-true="resource_group.is_active" />
                    </td>
                    <td class="px-6 py-4 text-right align-top">
                        <LinkGroup>
                            <ActionLink
                                v-if="hasPermission('edit_resource_groups', resource_group.institution_id)"
                                action="edit"
                                model="resource_group"
                                :params="{ id: resource_group.id }"
                            />
                            <RelationLink
                                v-if="hasPermission('view_resources', resource_group.institution_id)"
                                current="resource_group"
                                relation="resource"
                                :params="{ id: resource_group.id }"
                            />
                            <RelationLink
                                v-if="hasPermission('edit_institution', resource_group.institution_id)"
                                current="resource_group"
                                relation="setting"
                                :params="{ settingable_type: 'resource_group', settingable_id: resource_group.id }"
                            />
                            <PopupLink
                                v-if="hasPermission('delete_resource_groups', resource_group.institution_id)"
                                action="delete"
                                model="resource_group"
                                :params="{ id: resource_group.id }"
                            />
                        </LinkGroup>
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
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
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
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
