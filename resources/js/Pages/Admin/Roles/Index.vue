<template>
    <PageHead :title="$t('admin.roles.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.roles.index.title')" :description="$t('admin.roles.index.description')" />

    <PopupModal />
    <CreateAction model="role"></CreateAction>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.name") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.roles.index.table.header.permissions") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="role in roles"
                    :key="role.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(role.name) }}
                    </td>
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ translate(role.description) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{
                            role.permissions
                                .sort((a, b) => translate(a.name).localeCompare(translate(b.name)))
                                .map((permission) => translate(permission.name))
                                .join(", ")
                        }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link
                            :href="route('admin.role.edit', { id: role.id })"
                            class="font-medium text-red-600 dark:text-red-500 hover:underline"
                        >
                            {{ $t("admin.roles.index.table.actions.edit") }}
                        </Link>
                        |
                        <a
                            :href="route('admin.role.delete', { id: role.id })"
                            class="font-medium text-red-600 dark:text-red-500 hover:underline"
                            @click.prevent="modal.open({}, { message: $t('popup.content.delete.role') }, role, actions)"
                        >
                            {{ $t("admin.roles.index.table.actions.delete") }}
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
// IMPORTS
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import useModal from "@/Stores/Modal";
import CreateAction from "@/Components/Admin/CreateAction.vue";

import { router } from "@inertiajs/vue3";
import { Modal as FlowbiteModal } from "flowbite";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount, onMounted } from "vue";
import {useAppStore} from "@/Stores/AppStore";

// PROPS
defineProps({
    roles: {
        type: Object,
        default: () => ({}),
    },
});

// STORES
const modal = useModal();
const appStore = useAppStore();

// INJECT
const route = inject("route");
const translate = appStore.translate;

// STATE
const actions = [];

// LIFE CYCLE
onBeforeMount(() => {
    const deleteRoleLabel = computed(() => trans("popup.actions.delete"));

    const deleteRoleAction = {
        label: deleteRoleLabel,
        callback: (role) => {
            router.visit(route("admin.role.delete", { id: role.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteRoleAction);
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
