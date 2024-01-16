<template>
    <Transition
        enter-from-class="opacity-0 scale-125"
        enter-to-class="opacity-100 scale-100"
        enter-active-class="transition duration-300"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-125"
        leave-active-class="transition duration-200"
    >
        <div
            v-show="modal.isOpen"
            id="modal"
            tabindex="-1"
            aria-hidden="true"
            class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full"
        >
            <!-- Modal content -->
            <form
                ref="form"
                class="relative w-full max-w-2xl max-h-full bg-white rounded-lg shadow dark:bg-gray-800 dark:text-white p-4"
            >
                <!-- Close button -->
                <ModalCloseButton @close="modal.close"></ModalCloseButton>

                <component :is="view" v-model:payload="payload" :content="content" @submit="modal.submit()"></component>

                <!-- Footer -->
                <div class="pt-2 mt-2">
                    <!-- Action buttons -->
                    <div class="flex items-end space-x-2">
                        <button
                            v-for="action in actions"
                            :key="action.label"
                            class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                            type="button"
                            @click="action.callback(payload)"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Transition>
</template>

<script lang="ts" setup>
import ModalCloseButton from "@/Components/Modals/ModalCloseButton.vue";
import { useModal } from "@/Stores/Modal";

import { Modal as FlowbiteModal } from "flowbite";
import { storeToRefs } from "pinia";
import { onMounted, ref } from "vue";

const modal = useModal();
const { view, content, payload, actions } = storeToRefs(modal);

const form = ref(null);

onMounted(() => {
    modal.init(
        new FlowbiteModal(document.getElementById("modal"), {
            closable: true,
            placement: "center",
            backdrop: "dynamic",
            backdropClasses: "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                modal.cleanup();
            },
        }),
    );
});
</script>
