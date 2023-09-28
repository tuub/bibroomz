<template>
    <PageHead :title="$t('admin.happenings.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.happenings.index.title')" :description="$t('admin.happenings.index.description')" />

    <PopupModal />
    <CreateAction model="happening"></CreateAction>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.date") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.institution") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.resource_group") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.resource") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.start_time") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.end_time") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.user_01") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.happenings.index.table.header.user_02") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.happenings.index.table.header.is_verified") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.happenings.index.table.header.is_over") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="happening in happenings"
                    :key="happening.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ formatDate(happening.start) }}
                    </th>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.resource_group.institution.title) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.resource_group.name) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ translate(happening.resource.title) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ formatTime(happening.start) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ formatTime(happening.end) }}
                    </td>
                    <td class="px-6 py-4">
                        {{ happening.user1.name }}
                    </td>
                    <td class="px-6 py-4">
                        <span v-if="happening.is_verified && happening.user2">
                            {{ happening.user2.name }}
                        </span>
                        <span v-else-if="happening.verifier">
                            {{ happening.verifier }}
                        </span>
                        <span v-else>
                            {{ $t("admin.general.table.not_required") }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="happening.is_verified" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-else class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="isPastHappening(happening)" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-else class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span v-if="hasPermission('edit_happenings', happening.resource.institution_id)">
                            <Link
                                :href="
                                    route('admin.happening.edit', {
                                        id: happening.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.happenings.index.table.actions.edit") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('delete_happenings', happening.resource.institution_id)">
                            |
                            <a
                                :href="route('admin.happening.delete', { id: happening.id })"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                @click.prevent="
                                    modal.open(
                                        {},
                                        { message: $t('popup.content.delete.happening') },
                                        happening,
                                        actions,
                                    )
                                "
                            >
                                {{ $t("admin.happenings.index.table.actions.delete") }}
                            </a>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import useModal from "@/Stores/Modal";
import CreateAction from "@/Components/Admin/CreateAction.vue";

import { router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { Modal as FlowbiteModal } from "flowbite";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount, onMounted } from "vue";
import {useAuthStore} from "@/Stores/AuthStore";
import {useAppStore} from "@/Stores/AppStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    happenings: {
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
const modal = useModal();
const appStore = useAppStore();
const authStore = useAuthStore();
const { hasPermission } = authStore;
// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatDate = (dataTime) => {
    return dayjs.utc(dataTime).format("DD.MM.YYYY");
};

const formatTime = (time) => {
    return dayjs.utc(time).format("HH:mm");
};

const isPastHappening = (happening) => {
    return dayjs(happening.end).isBefore(dayjs().utcOffset(0, true));
};

// ------------------------------------------------
// Variables
// ------------------------------------------------
const actions = [];
const route = inject("route");
const translate = appStore.translate;

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const deleteHappeningLabel = computed(() => trans("popup.actions.delete"));

    const deleteHappeningAction = {
        label: deleteHappeningLabel,
        callback: (happening) => {
            router.visit(route("admin.happening.delete", { id: happening.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteHappeningAction);
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
