<template>
    <PageHead :title="$t('admin.mails.index.title', { title: translate(institution.title) })" page-type="admin" />
    <BodyHead
        :title="$t('admin.mails.index.title', { title: translate(institution.title) })"
        :description="$t('admin.mails.index.description')"
    />

    <PopupModal />
    <CreateAction model="mail" :params="{ institution_id: institution.id }"></CreateAction>

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
                <tr
                    v-for="mail in mails"
                    :key="mail.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white"
                    >
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
                        <span v-if="hasPermission('edit_mails', mail.institution_id)">
                            <Link
                                :href="
                                    route('admin.mail.edit', {
                                        id: mail.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.mails.index.table.actions.edit") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('delete_mails', mail.institution_id)">
                            |
                            <a
                                :href="route('admin.mail.delete', { id: mail.id })"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                @click.prevent="
                                    modal.open({}, { message: $t('popup.content.delete.mail') }, mail, actions)
                                "
                            >
                                {{ $t("admin.mails.index.table.actions.delete") }}
                            </a>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import CreateAction from "@/Components/Admin/CreateAction.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import { useAuthStore } from "@/Stores/AuthStore";
import {useAppStore} from "@/Stores/AppStore";
import useModal from "@/Stores/Modal";

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
const modal = useModal();

const { hasPermission } = authStore;

const route = inject("route");
const translate = appStore.translate;

// ------------------------------------------------
// Variables
// ------------------------------------------------
const actions = [];

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const deleteMailLabel = computed(() => trans("popup.actions.delete"));

    const deleteMailAction = {
        label: deleteMailLabel,
        callback: (mail) => {
            router.visit(route("admin.mail.delete", { id: mail.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteMailAction);
});

onMounted(() => {
    modal.init(
        new FlowbiteModal(document.getElementById("popup-modal"), {
            closable: true,
            placement: "center",
            backdrop: "dynamic",
            backdropClasses: "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                modal.cleanup();
            },
        }),
    );
});
</script>
