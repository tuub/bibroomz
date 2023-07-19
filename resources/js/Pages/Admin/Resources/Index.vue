<template>
    <PageHead title="Admin Resources Form" page_type="admin" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Resources</h1>
            <Link href="/admin/resource/create" class="text-blue-500 text-sm ml-3">Create Resource</Link>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Resource Title
                </th>
                <th scope="col" class="px-6 py-3">
                    Location
                </th>
                <th scope="col" class="px-6 py-3">
                    Institution
                </th>
                <th scope="col" class="px-6 py-3">
                    Business hours
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Capacity
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Active
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Verification required
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">Actions</span>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="resource in resources"
                :key="resource.id"
                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
                <th scope="row" class="px-6 py-4 align-top font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ resource.title }}
                </th>
                <td class="px-6 py-4 align-top">
                    {{ resource.location }}
                </td>
                <td class="px-6 py-4 align-top">
                    {{ resource.institution.title }}
                </td>
                <td class="px-6 py-4 align-top">
                    <p v-for="business_hour in resource.business_hours">
                        {{ formatTime(business_hour.start) }} - {{ formatTime(business_hour.end) }}
                        (
                            {{ business_hour.week_days.map(entry => entry.name).join(', ') }}
                        )
                    </p>
                </td>
                <td class="px-6 py-4 align-top text-center">
                    {{ resource.capacity }}
                </td>
                <td class="px-6 py-4 align-top text-center">
                    <i class="ri-checkbox-circle-line text-green-500" v-if="resource.is_active"></i>
                    <i class="ri-close-circle-line text-red-500" v-if="!resource.is_active"></i>
                </td>
                <td class="px-6 py-4 align-top text-center">
                    <i class="ri-checkbox-circle-line text-green-500" v-if="resource.is_verification_required"></i>
                    <i class="ri-close-circle-line text-red-500" v-if="!resource.is_verification_required"></i>
                </td>
                <td class="px-6 py-4 align-top text-right">
                    <Link :href="route('admin.resource.edit', {
                        id: resource.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        Edit
                    </Link>
                    |
                    <Link :href="route('admin.closing.index', {
                        closable_type: 'resource',
                        closable_id: resource.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                        Closings
                    </Link>
                    |
                    <Link method="post" as="button" :href="route('admin.resource.delete', {
                        id: resource.id,
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
import customParseFormat from 'dayjs/plugin/customParseFormat';
import PageHead from "@/Shared/PageHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    resources: Object,
    filters: Object,
})

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(customParseFormat)

// ------------------------------------------------
// Methods
// ------------------------------------------------
const formatTime = ((time) => {
    return dayjs(time, 'HH:mm:ss').format('HH:mm');
})
</script>
