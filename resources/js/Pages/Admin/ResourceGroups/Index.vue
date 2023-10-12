<template>
    <PageHead :title="$t('admin.resource_groups.index.title')" page-type="admin" />
    <BodyHead
        :title="$t('admin.resource_groups.index.title')"
        :description="$t('admin.resource_groups.index.description')"
    />

    <PopupModal />
    <CreateLink model="resource_group"></CreateLink>

    <div class="relative overflow-x-auto shadow-md">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resource_groups.index.table.header.name") }}
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
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white"
                    >
                        {{ translate(resource_group.name) }}
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
                    <td class="px-6 py-4 align-top text-center">
                        <BooleanField :is-true="resource_group.is_active" />
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        <ActionLink
                            v-if="hasPermission('edit_resource_groups', resource_group.institution_id)"
                            action="edit"
                            model="resource_group"
                            :params="{ id: resource_group.id }"
                        />
                        |
                        <RelationLink
                            v-if="hasPermission('view_resources', resource_group.institution_id)"
                            current="resource_group"
                            relation="resource"
                            :params="{ id: resource_group.id }"
                        />
                        |
                        <DeleteLink
                            v-if="hasPermission('delete_resource_groups', resource_group.institution_id)"
                            model="resource_group"
                            :entity="resource_group"
                            :params="{ id: resource_group.id }"
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
