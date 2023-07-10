<template>
    <Head title="Admin/Happenings" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Happenings</h1>
            <Link href="/admin/happening/create" class="text-blue-500 text-sm ml-3">Create Happening</Link>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Date
                </th>
                <th scope="col" class="px-6 py-3">
                    Resource
                </th>
                <th scope="col" class="px-6 py-3">
                    Start
                </th>
                <th scope="col" class="px-6 py-3">
                    End
                </th>
                <th scope="col" class="px-6 py-3">
                    User #1
                </th>
                <th scope="col" class="px-6 py-3">
                    User #2
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Confirmed
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
            <tr v-for="happening in happenings"
                :key="happening.id"
                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ formatDate(happening.start) }}
                </th>
                <td class="px-6 py-4">
                    {{ happening.resource.title }}
                </td>
                <td class="px-6 py-4">
                    {{ formatTime(happening.start) }}
                </td>
                <td class="px-6 py-4">
                    {{ formatTime(happening.end) }}
                </td>
                <td class="px-6 py-4">
                    {{ happening.user1.name }}
                </td>
                <td class="px-6 py-4">
                    <span v-if="happening.user2">
                        {{ happening.user2.name }}
                    </span>
                    <span v-else>
                        ({{ happening.confirmer }})
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <i class="ri-checkbox-circle-line text-green-500" v-if="happening.is_confirmed"></i>
                    <i class="ri-close-circle-line text-red-500" v-if="!happening.is_confirmed"></i>
                </td>
                <td class="px-6 py-4 text-center">
                    FIXME
                </td>
                <td class="px-6 py-4 text-right">
                    <Link :href="route('admin.happening.edit', {
                        id: happening.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        Edit
                    </Link>
                    |
                    <Link method="post"
                          as="button"
                          :href="route('admin.happening.delete', {
                          id: happening.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
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
import utc from 'dayjs/plugin/utc';

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    happenings: Object,
})

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc)

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatDate = ((dataTime) => {
    return dayjs.utc(dataTime).format('DD.MM.YYYY');
})

const formatTime = ((time) => {
    return dayjs.utc(time).format('HH:mm');
})
</script>
