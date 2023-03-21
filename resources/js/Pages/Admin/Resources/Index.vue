<template>
    <Head title="Admin/Resources" />

    <div class="flex justify-between mb-6">
        <div class="flex items-center">
            <h1 class="text-3xl">Resources</h1>
            <Link href="/admin/resources/create" class="text-blue-500 text-sm ml-3">Create Resource</Link>
        </div>

        <input v-model="search" type="text" placeholder="Search..." class="border px-2 rounded-lg" />
    </div>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="resource in resources.data" :key="resource.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ resource.title }}, {{ resource.location }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link :href="`/admin/resources/${resource.id}/edit`" class="text-indigo-600 hover:text-indigo-900"> Edit</Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, watch} from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

let props = defineProps({
    resources: Object,
    filters: Object,
})

let search = ref(props.filters.search)

watch(search, debounce(function (value) {
    console.log('Triggered')
    router.get(
        '/admin/resources',
        { search: value },
        { preserveState: true, replace: true });
}, 300));
</script>
