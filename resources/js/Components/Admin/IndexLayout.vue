<template>
    <div>
        <XModal />

        <CreateLink v-if="addCreateButton" :model="model" :params="createParams" />

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="text-app-muted dark:text-app-subtle w-full text-left text-sm">
                <thead class="bg-app-field text-app-muted dark:bg-app-field dark:text-app-subtle text-xs uppercase">
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

<script setup lang="ts">
import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
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
