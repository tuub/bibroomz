<template>
    <PageHead :title="$t('admin.closings.index.title',{type: closable_type, title: closable.title})"
              page_type="admin" />
    <BodyHead :title="$t('admin.closings.index.title', {type: closable_type, title: closable.title})"
              :description="$t('admin.closings.index.description')" />

    <div>
        <Link :href="route('admin.closing.create', {
            closable_type: closable_type,
            closable_id: closable.id
        })">{{ $t('admin.closings.index.table.actions.create') }}</Link>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.closings.index.table.header.start') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.closings.index.table.header.end') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ $t('admin.closings.index.table.header.description') }}
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    {{ $t('admin.closings.index.table.header.is_over') }}
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">{{ $t('admin.general.table.actions') }}</span>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="closing in closings"
                :key="closing.id"
                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
                <td class="px-6 py-4">
                    {{ formatDateTime(closing.start) }}
                </td>
                <td class="px-6 py-4">
                    {{ formatDateTime(closing.end) }}
                </td>
                <td class="px-6 py-4">
                    {{ closing.description }}
                </td>
                <td class="px-6 py-4 text-center">
                    <i v-if="isPastClosing(closing)" class="ri-checkbox-circle-line text-green-500"></i>
                    <i v-else class="ri-close-circle-line text-red-500"></i>
                </td>
                <td class="px-6 py-4 text-right">
                    <Link :href="route('admin.closing.edit', {
                        id: closing.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.closings.index.table.actions.edit') }}
                    </Link>
                    |
                    <Link method="post"
                          as="button"
                          :href="route('admin.closing.delete')"
                          :data="{
                              id: closing.id,
                              closable_id: closing.closable_id,
                              closable_type: closing.closable_type,
                          }"
                          preserve-scroll
                          class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        {{ $t('admin.closings.index.table.actions.delete') }}
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
import utc from 'dayjs/plugin/utc';
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    closings: Object,
    closable: Object,
    closable_type: String,
    filters: Object,
})

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(customParseFormat)
dayjs.extend(utc)

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatDateTime = ((dataTime) => {
    return dayjs.utc(dataTime).format('DD.MM.YYYY HH:mm');
})

const isPastClosing = (closing) => {
    return dayjs(closing.end).isBefore(dayjs().utcOffset(0, true));
}
</script>
