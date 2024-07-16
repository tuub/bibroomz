<template>
    <Dialog
        id="modal"
        v-model:visible="modal.isOpen"
        dismissable-mask
        :header="modal.content?.title"
        modal
        class="w-[46rem] max-w-[95%]"
        @hide="modal.cleanup"
    >
        <!-- Modal content -->
        <component :is="view" v-model:payload="payload" :content="content" @submit="modal.submit()"></component>

        <!-- Action buttons -->
        <div class="mt-2 flex items-end space-x-2 pt-2">
            <button
                v-for="action in actions"
                :key="action.label"
                class="rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                type="button"
                @click="action.callback(payload)"
            >
                {{ action.label }}
            </button>
        </div>
    </Dialog>
</template>

<script setup>
import { useModal } from "@/Stores/Modal";

import { storeToRefs } from "pinia";
import Dialog from "primevue/dialog";

const modal = useModal();
const { view, content, payload, actions } = storeToRefs(modal);
</script>
