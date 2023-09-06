<template>
    <PageHead :title="$t('admin.institutions.index.title')" page-type="admin" />
    <BodyHead :title="$t('admin.institutions.index.title')" :description="$t('admin.institutions.index.description')" />

    <PopupModal />

    <div>
        <Link v-if="hasPermission('create institutions')" :href="route('admin.institution.create')">{{
            $t("admin.institutions.index.table.actions.create")
        }}</Link>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.short_title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.slug") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.location") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.institutions.index.table.header.resources") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.institutions.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="institution in institutions"
                    :key="institution.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ institution.title }}
                    </th>
                    <td class="px-6 py-4">
                        {{ institution.short_title }}
                    </td>
                    <td class="px-6 py-4">
                        {{ institution.slug }}
                    </td>
                    <td class="px-6 py-4">
                        {{ institution.location }}
                    </td>
                    <td class="px-6 py-4">
                        {{ institution.resources.length }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i v-if="institution.is_active" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!institution.is_active" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span v-if="hasPermission('edit institution', institution.id)">
                            <Link
                                :href="
                                    route('admin.institution.edit', {
                                        id: institution.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.institutions.index.table.actions.edit") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('view closings', institution.id)">
                            |
                            <Link
                                :href="
                                    route('admin.closing.index', {
                                        closable_type: 'institution',
                                        closable_id: institution.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.institutions.index.table.actions.closings") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('edit institution', institution.id)">
                            |
                            <Link
                                :href="
                                    route('admin.setting.index', {
                                        id: institution.id,
                                    })
                                "
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            >
                                {{ $t("admin.institutions.index.table.actions.settings") }}
                            </Link>
                        </span>
                        <span v-if="hasPermission('delete institution', institution.id)">
                            |
                            <a
                                :href="route('admin.institution.delete', { id: institution.id })"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                @click.prevent="
                                    modal.open(
                                        {},
                                        { message: $t('popup.content.delete.institution') },
                                        institution,
                                        actions
                                    )
                                "
                            >
                                {{ $t("admin.institutions.index.table.actions.delete") }}
                            </a>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PopupModal from "@/Shared/PopupModal.vue";
import useModal from "@/Stores/Modal";
import { computed, inject, onBeforeMount, onMounted } from "vue";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import { Modal as FlowbiteModal } from "flowbite";
import { useAuthStore } from "@/Stores/AuthStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    institutions: {
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
const modal = useModal();

const { hasPermission } = authStore;

// ------------------------------------------------
// Variables
// ------------------------------------------------
const route = inject("route");

const actions = [];

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const deleteInstitutionLabel = computed(() => trans("popup.actions.delete"));

    const deleteInsitutionAction = {
        label: deleteInstitutionLabel,
        callback: (institution) => {
            router.visit(route("admin.institution.delete", { id: institution.id }), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteInsitutionAction);
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
