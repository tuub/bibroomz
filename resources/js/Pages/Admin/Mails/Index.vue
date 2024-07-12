<template>
    <PageHead :title="$t('admin.mails.index.title', { title: translate(institution.title) })" page-type="admin" />
    <BodyHead
        :title="$t('admin.mails.index.title', { title: translate(institution.title) })"
        :description="$t('admin.mails.index.description')"
    />

    <XModal />
    <CreateLink model="mail" :params="{ institution_id: institution.id }"></CreateLink>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
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
                <tr
                    v-for="mail in mails"
                    :key="mail.id"
                    class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="whitespace-nowrap px-6 py-4 align-top font-medium text-gray-900 dark:text-white"
                    >
                        {{ $t("admin.mails.mail_types." + mail.mail_type.key) }}
                    </th>
                    <td class="px-6 py-4 align-top">
                        {{ translate(mail.subject) }}
                    </td>
                    <td class="px-6 py-4 text-center align-top">
                        <BooleanField :is-true="mail.is_active" />
                    </td>
                    <td class="px-6 py-4 text-right align-top">
                        <LinkGroup>
                            <ActionLink
                                v-if="hasPermission('edit_mails', mail.institution_id)"
                                action="edit"
                                model="mail"
                                :params="{ id: mail.id }"
                            />
                            <PopupLink
                                v-if="hasPermission('delete_mails', mail.institution_id)"
                                action="delete"
                                model="mail"
                                :params="{ id: mail.id }"
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
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

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
