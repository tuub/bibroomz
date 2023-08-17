<template>
    <PageHead :title="$t('admin.resources.index.title')" page_type="admin" />
    <BodyHead :title="$t('admin.resources.index.title')" :description="$t('admin.resources.index.description')" />

    <PopupModal />

    <div>
        <Link :href="route('admin.resource.create')">{{ $t("admin.resources.index.table.actions.create") }}</Link>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.title") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.location") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.institution") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.resources.index.table.header.business_hours") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.capacity") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.is_active") }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center">
                        {{ $t("admin.resources.index.table.header.is_verification_required") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="resource in resources"
                    :key="resource.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th
                        scope="row"
                        class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white"
                    >
                        {{ resource.title }}
                    </th>
                    <td class="px-6 py-4 align-top">
                        {{ resource.location }}
                    </td>
                    <td class="px-6 py-4 align-top">
                        {{ resource.institution.title }}
                    </td>
                    <td class="px-6 py-4 align-top">
                        <p v-for="business_hour in resource.business_hours" :key="business_hour.id">
                            {{ formatTime(business_hour.start) }} - {{ formatTime(business_hour.end) }}
                            (
                            {{ business_hour.week_days.map((entry) => entry.name).join(", ") }}
                            )
                        </p>
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        {{ resource.capacity }}
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <i v-if="resource.is_active" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!resource.is_active" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <i v-if="resource.is_verification_required" class="ri-checkbox-circle-line text-green-500"></i>
                        <i v-if="!resource.is_verification_required" class="ri-close-circle-line text-red-500"></i>
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        <Link
                            :href="
                                route('admin.resource.edit', {
                                    id: resource.id,
                                })
                            "
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                        >
                            {{ $t("admin.resources.index.table.actions.edit") }}
                        </Link>
                        |
                        <Link
                            :href="
                                route('admin.closing.index', {
                                    closable_type: 'resource',
                                    closable_id: resource.id,
                                })
                            "
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                        >
                            {{ $t("admin.resources.index.table.actions.closings") }}
                        </Link>
                        |
                        <a
                            :href="route('admin.resource.delete', { id: resource.id })"
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            @click.prevent="
                                modal.open({}, { message: $t('popup.content.delete.resource') }, resource, actions)
                            "
                        >
                            {{ $t("admin.resources.index.table.actions.delete") }}
                        </a>
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
import { computed, inject, onMounted } from "vue";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import { Modal as FlowbiteModal } from "flowbite";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    resources: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(customParseFormat);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatTime = (time) => {
    return dayjs(time, "HH:mm:ss").format("HH:mm");
};

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();
const route = inject("route");

const actions = [];

const deleteResourceLabel = computed(() => trans("popup.actions.delete"));

const deleteResourceAction = {
    label: deleteResourceLabel,
    callback: (resource) => {
        router.visit(route("admin.resource.delete", { id: resource.id }), {
            method: "post",
            preserveScroll: true,
        });
    },
};

actions.push(deleteResourceAction);

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
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
