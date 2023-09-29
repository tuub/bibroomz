<template>
    <PageHead :title="$t('admin.resource_groups.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.resource_groups.index.title')" :description="$t('admin.resource_groups.index.description')" />

    <PopupModal />
    <CreateAction model="resource_group"></CreateAction>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
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
                        <i v-if="resource_group.is_active" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!resource_group.is_active" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        <span v-if="hasPermission('edit_resource_groups', resource_group.institution_id)">
                            <Link
                                :href="
                                    route('admin.resource_group.edit', {
                                        id: resource_group.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.resource_groups.index.table.actions.edit") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('view_resources', resource_group.institution_id)">
                            |
                            <Link
                                :href="
                                    route('admin.resource.index', {
                                        id: resource_group.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.resource_groups.index.table.actions.resources") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('delete_resource_groups', resource_group.institution_id)">
                            |
                            <a
                                :href="route('admin.resource_group.delete', { id: resource_group.id })"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                @click.prevent="
                                    modal.open({}, { message: $t('popup.content.delete.resource') }, resource_group, actions)
                                "
                            >
                                {{ $t("admin.resource_groups.index.table.actions.delete") }}
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
import { useAuthStore } from "@/Stores/AuthStore";
import { useAppStore } from "@/Stores/AppStore";
import useModal from "@/Stores/Modal";
import CreateAction from "@/Components/Admin/CreateAction.vue";

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
    resource_groups: {
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
// Methods
// ------------------------------------------------
const formatTime = (time) => {
    return dayjs(time, "HH:mm:ss").format("HH:mm");
};

// ------------------------------------------------
// Variables
// ------------------------------------------------
const actions = [];

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const deleteResourceGroupLabel = computed(() => trans("popup.actions.delete"));

    const deleteResourceGroupAction = {
        label: deleteResourceGroupLabel,
        callback: (resource_group) => {
            router.visit(route("admin.resource_group.delete", { id: resource_group.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteResourceGroupAction);
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