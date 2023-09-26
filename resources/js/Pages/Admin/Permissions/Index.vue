<template>
    <PageHead :title="$t('admin.permissions.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.permissions.index.title')" :description="$t('admin.permissions.index.description')" />

    <PopupModal />

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.permissions.index.table.header.name") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.permissions.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(permission.name) }}
                    </td>
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(permission.description) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link
                            :href="route('admin.permission.edit', { id: permission.id })"
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                        >
                            {{ $t("admin.permissions.index.table.actions.edit") }}
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import useModal from "@/Stores/Modal";

import { router } from "@inertiajs/vue3";
import { Modal as FlowbiteModal } from "flowbite";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount, onMounted } from "vue";

defineProps({
    permissions: {
        type: Object,
        default: () => ({}),
    },
});

const modal = useModal();
const route = inject("route");
const translate = inject("translate");

const actions = [];

onBeforeMount(() => {
    const deletePermissionLabel = computed(() => trans("popup.actions.delete"));

    const deletePermissionAction = {
        label: deletePermissionLabel,
        callback: (permission) => {
            router.visit(route("admin.permission.delete", { id: permission.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deletePermissionAction);
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
