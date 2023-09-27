<template>
    <PageHead :title="$t('admin.users.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.users.index.title')" :description="$t('admin.users.index.description')" />

    <PopupModal />

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
                        {{ $t("admin.users.index.table.header.is_admin") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_privileged") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.users.index.table.header.is_banned") }}
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
                        <i v-if="user.is_admin" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-else class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="user.roles?.length > 0" class="ri-checkbox-circle-line text-green-500"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="user.banned_at === ''" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="user.banned_at !== ''" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ user.happenings.length }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link
                            :href="route('admin.user.edit', { id: user.id })"
                            class="font-medium text-red-600 dark:text-red-500 hover:underline"
                        >
                            {{ $t("admin.users.index.table.actions.edit") }}
                        </Link>
                        |
                        <a
                            :href="route('admin.user.delete', { id: user.id })"
                            class="font-medium text-red-600 dark:text-red-500 hover:underline"
                            @click.prevent="modal.open({}, { message: $t('popup.content.delete.user') }, user, actions)"
                        >
                            {{ $t("admin.users.index.table.actions.delete") }}
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
// IMPORTS
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import useModal from "@/Stores/Modal";
import { computed, inject, onBeforeMount, onMounted } from "vue";
import { Modal as FlowbiteModal } from "flowbite";
import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";

// PROPS
defineProps({
    users: {
        type: Object,
        default: () => ({}),
    },
});

// STORES
const modal = useModal();

// INJECT
const route = inject("route");

// STATE
const actions = [];

// LIFE CYCLE
onBeforeMount(() => {
    const deleteUserLabel = computed(() => trans("popup.actions.delete"));

    const deleteUserAction = {
        label: deleteUserLabel,
        callback: (user) => {
            router.visit(route("admin.user.delete", { id: user.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteUserAction);
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
        })
    );
});
</script>
