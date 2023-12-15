<template>
    <PageHead :title="$t('admin.users.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.index.title')" :description="$t('admin.users.index.description')" />

    <PopupModal />
    <CreateLink model="user"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.users.index.table.header.name") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.users.index.table.header.email") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_system_user") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_admin") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_privileged") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_logged_in") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.happenings") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ user.name }}
                    </th>
                    <td class="px-6 py-4">
                        {{ user.email }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_system_user" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_admin" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_privileged" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <BooleanField :is-true="user.is_logged_in" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ user.happenings_count }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink action="edit" model="user" :params="{ id: user.id }" />
                        |
                        <DeleteLink model="user" :entity="user" :params="{ id: user.id }" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import DeleteLink from "@/Components/Admin/Index/DeleteLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    users: {
        type: Object,
        default: () => ({}),
    },
});
</script>
