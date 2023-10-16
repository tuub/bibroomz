<template>
    <PageHead :title="$t('admin.settings.index.title', {
        type: $t('admin.settings.types.' + settingable_type),
        title: translate(settingable.title)
    })" page-type="admin"/>

    <BodyHead :title="$t('admin.settings.index.title', {
        type: $t('admin.settings.types.' + settingable_type),
        title: translate(settingable.title)
    })" :description="$t('admin.settings.index.description')"/>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.settings.index.table.header.key") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.settings.index.table.header.description") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        {{ $t("admin.settings.index.table.header.value") }}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">{{ $t("admin.general.table.actions") }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="setting in settings"
                    :key="setting.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $t("admin.settings.keys." + setting.key + ".label") }}
                    </th>
                    <td class="px-6 py-4">
                        {{ $t("admin.settings.keys." + setting.key + ".description") }}
                    </td>
                    <td class="px-6 py-4">
                        {{ setting.value }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <ActionLink action="edit" model="setting" :params="{ id: setting.id }" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import { useAppStore } from "@/Stores/AppStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    settingable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    settingable_type: {
        type: String,
        default: "",
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
</script>
