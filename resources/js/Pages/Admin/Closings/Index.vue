<template>
    <PageHead title="Admin Closings Index" page_type="admin" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Closings for {{ closable_type }} "{{ closable.title }}"</h1>
            <Link :href="route('admin.closing.create', {closable_type: closable_type, closable_id: closable.id})" class="text-blue-500 text-sm ml-3">Create Closing</Link>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Start
                </th>
                <th scope="col" class="px-6 py-3">
                    End
                </th>
                <th scope="col" class="px-6 py-3">
                    Description
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Is over?
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">Actions</span>
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
                    FIXME
                </td>
                <td class="px-6 py-4 text-right">
                    <Link :href="route('admin.closing.edit', {
                        id: closing.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        Edit
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
                        Delete
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
import {usePage} from "@inertiajs/vue3";
import PageHead from "@/Shared/PageHead.vue";

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
// Variables
// ------------------------------------------------
let $page = usePage()

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatDateTime = ((dataTime) => {
    return dayjs.utc(dataTime).format('DD.MM.YYYY HH:mm');
})
</script>
