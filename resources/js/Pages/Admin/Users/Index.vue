<template>
    <Head title="Admin/Users" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Users</h1>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Name
                </th>
                <th scope="col" class="px-6 py-3">
                    E-Mail
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Admin
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Banned
                </th>
                <th scope="col" class="px-6 py-3 text-center">
                    Reservations
                </th>
                <th scope="col" class="px-6 py-3">
                    <span class="sr-only">Edit</span>
                </th>
            </tr>
            </thead>
            <tbody>
                <tr v-for="user in users"
                    :key="user.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ user.name }}
                    </th>
                    <td class="px-6 py-4">
                        {{ user.email }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i class="ri-checkbox-circle-line text-green-500" v-if="user.is_admin"></i>
                        <i class="ri-close-circle-line text-red-500" v-if="!user.is_admin"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <i class="ri-checkbox-circle-line text-green-500" v-if="user.banned_at === ''"></i>
                        <i class="ri-close-circle-line text-red-500" v-if="user.banned_at !== ''"></i>
                    </td>
                    <td class="px-6 py-4 text-center">
                        FIXME
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link :href="route('admin.user.edit', {
                        id: user.id,
                    })" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                            Edit
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
let props = defineProps({
    users: Object,
})
</script>
