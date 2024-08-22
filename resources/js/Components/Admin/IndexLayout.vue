<template>
    <div>
        <PageHead :title="title" page-type="admin" />
        <BodyHead :title="title" :description="description" />

        <XModal />

        <CreateLink v-if="addCreateButton" :model="model" :params="createParams" />

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <slot name="header" />
                    </tr>
                </thead>
                <tbody>
                    <slot name="body" />
                </tbody>
            </table>
        </div>

        <div v-if="$slots.footer" class="mt-2 flex justify-center">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    addCreateButton: {
        type: Boolean,
        default: true,
    },
    model: {
        type: String,
        default: "",
    },
    createParams: {
        type: Object,
        default: () => ({}),
    },
});
</script>
