<template>
    <PageHead title="Admin Settings Index" page_type="admin" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Settings</h1>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Key
                </th>
                <th scope="col" class="px-6 py-3">
                    Description
                </th>
                <th scope="col" class="px-6 py-3">
                    Default Value
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody>
                <tr v-for="setting in settings"
                    :key="setting.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ setting.key }}
                    </th>
                    <td class="px-6 py-4">
                        {{ setting.description }}
                    </td>
                    <td class="px-6 py-4">
                        {{ setting.value }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link :href="route('admin.setting.edit', {id: setting.id})"
                              class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                            Edit
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
import PageHead from "@/Shared/PageHead.vue";

let props = defineProps({
    settings: Object,
})
</script>
