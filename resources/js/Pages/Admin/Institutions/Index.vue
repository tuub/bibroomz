<template>
    <Head title="Admin/Resources" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Institutions</h1>
            <Link href="/admin/institution/create" class="text-blue-500 text-sm ml-3">Create Institution</Link>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Institution Title
                </th>
                <th scope="col" class="px-6 py-3">
                    Short Title
                </th>
                <th scope="col" class="px-6 py-3">
                    Slug
                </th>
                <th scope="col" class="px-6 py-3">
                    Location
                </th>
                <th scope="col" class="px-6 py-3">
                    # Resources
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Active
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">Actions</span>
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
                        Edit
                    </Link>
                    |
                    <Link :href="route('admin.closing.index', {
                        closable_type: 'institution',
                        closable_id: institution.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        Closings
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
dayjs.extend(customParseFormat)

let props = defineProps({
    institutions: Object,
    filters: Object,
})
</script>
