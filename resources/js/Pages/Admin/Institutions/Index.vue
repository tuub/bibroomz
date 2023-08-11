<template>
    <PageHead :title="$t('admin.institutions.index.title')" page_type="admin" />
    <BodyHead :title="$t('admin.institutions.index.title')"
              :description="$t('admin.institutions.index.description')">
    </BodyHead>

    <div>
        <Link :href="route('admin.institution.create')">{{ $t('admin.institutions.index.table.actions.create') }}</Link>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.institutions.index.table.header.title') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.institutions.index.table.header.short_title') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.institutions.index.table.header.slug') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.institutions.index.table.header.location') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.institutions.index.table.header.resources') }}
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    {{ $t('admin.institutions.index.table.header.is_active') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">{{ $t('admin.general.actions') }}</span>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="institution in institutions"
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
                    <i class="ri-checkbox-circle-line text-green-500" v-if="institution.is_active"></i>
                    <i class="ri-close-circle-line text-red-500" v-if="!institution.is_active"></i>
                </td>
                <td class="px-6 py-4 text-right">
                    <Link :href="route('admin.institution.edit', {
                        id: institution.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.institutions.index.table.actions.edit') }}
                    </Link>
                    |
                    <Link :href="route('admin.closing.index', {
                        closable_type: 'institution',
                        closable_id: institution.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.institutions.index.table.actions.closings') }}
                    </Link>
                    |
                    <Link :href="route('admin.setting.index', {
                        id: institution.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.institutions.index.table.actions.settings') }}
                    </Link>
                    |
                    <Link method="post"
                          as="button"
                          :href="route('admin.institution.delete', {id: institution.id})"
                          class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.institutions.index.table.actions.delete') }}
                    </Link>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import dayjs from "dayjs";
import customParseFormat from 'dayjs/plugin/customParseFormat';
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    institutions: Object,
    filters: Object,
})

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(customParseFormat)
</script>
