<template>
    <vue-final-modal
        classes="modal fade outline-none overflow-x-hidden overflow-y-auto flex items-center justify-center h-screen"
        content-class="relative flex flex-col w-1/4 max-h-full mx-4 p-4 border dark:border-gray-800 rounded bg-white dark:bg-gray-900 rounded-lg"
    >
        <!-- button close -->
        <button @click="closeModal"
            class="absolute -top-3 -right-3 h-10 w-10 rounded-full bg-red-500 text-2xl text-white hover:bg-red-600 focus:outline-none"
        >
            &cross;
        </button>
        <!-- header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-600">{{ modalTitle }}</h2>
        </div>

        <!-- body -->
        <div class="w-full p-3 italic ">
            <slot name="modalTextBody"></slot>
            <p class="mb-5">{{ modalContent }}</p>
            <p class="mb-5">{{ modalContent }}</p>
            <p class="mb-5">{{ modalContent }}</p>
            <p class="mb-5">{{ modalContent }}</p>
            <p class="mb-5">{{ modalContent }}</p>
        </div>

        <!-- footer -->
        <div class="absolute bottom-0 left-0 px-4 py-3 border-t border-gray-200 w-full flex justify-end items-center gap-3">
            <!--
            <button class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded text-white focus:outline-none">{{ buttonText }}</button>
            -->
            <button @click="closeModal" class="bg-gray-500 hover:bg-gray-600 px-4 py-2 rounded text-white focus:outline-none">
                {{ modalButtonText }}
            </button>
            <slot name="modalActions"></slot>
        </div>
    </vue-final-modal>

</template>

<script>
import {$vfm} from "vue-final-modal";

export default {
    name: "NotificationModal",
    props: {
        modalTitle: String,
        modalContent: String,
        modalButtonText: String
    },
    setup() {
        const closeModal = () => {
            $vfm.hideAll()
        }

        return {
            closeModal
        }
    }
};
</script>

