<template>
    <div class="flex items-center space-x-4">
        <div class="flex-1 min-w-0"  :class="isPastHappening ? 'text-gray-400' : 'text-gray-900'">
            <span v-if="isPastHappening"
                class="text-xs font-normal inline-block py-0.5 px-2 uppercase rounded text-black bg-gray-200 uppercase last:mr-0 mr-1">
                Past Event
            </span>
            <p class="font-bold truncate dark:text-white pb-1">
                <i class="ri-calendar-event-line"></i>
                {{ happeningDate }}
                <i class="ri-arrow-right-line"></i>
                {{ happeningStart }} - {{ happeningEnd }}
            </p>
            <p class="text-sm font-medium pb-1">
                <i class="ri-home-line"></i>
                {{ happening.resource.title }}
            </p>
            <p class="text-sm font-medium truncate pb-1">
                <i class="ri-map-pin-fill"></i>
                {{ happening.resource.location }}
            </p>
            <p class="text-sm font-medium pb-1">
                <i class="ri-user-fill"></i>
                {{ happening.user_01 }}
            </p>
            <p class="text-sm font-medium pb-1">
                <i class="ri-user-follow-fill"></i>
                {{ happening.user_02 }}
                <span v-if="!isPastHappening && happening.is_confirmed"
                      class="text-xs font-normal inline-block py-0.5 px-2 uppercase rounded text-black bg-green-300 uppercase last:mr-0 mr-1">
                    <i class="ri-check-line"></i>
                    Confirmed
                </span>
            </p>
        </div>
        <!-- FIXME: do this in policy! -->
        <div v-if="!isPastHappening" class="text-base font-bold text-gray-900">
            <p>
                <a v-if="happening.can.delete"
                   @click="deleteUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-red-200 border border-gray-200 rounded-lg hover:bg-red-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-delete-bin-line mr-1"></i>
                    Delete
                </a>
            </p>
            <p>
                <a v-if="happening.can.confirm"
                   @click="confirmUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-check-double-line mr-1"></i>
                    Confirm
                </a>
            </p>
            <p>
                <a v-if="happening.can.edit"
                   @click="editUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-edit-line mr-1"></i>
                    Edit
                </a>
            </p>
        </div>
    </div>
</template>

<script setup>
import {computed} from "vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import {useHappeningStore} from "../Stores/HappeningStore"
import useModal from "../Stores/Modal";
import { useConfirmModal, useDeleteModal, useEditModal } from "@/Composables/modalActions";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    happening: Object,
})

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal()
const happeningDate = computed(() => {
    return dayjs.utc(props.happening.start).format('DD.MM.YYYY');
})

const happeningStart = computed(() => {
    return dayjs.utc(props.happening.start).format('HH:mm');
})

const happeningEnd = computed(() => {
    return dayjs.utc(props.happening.end).format('HH:mm');
})

const isPastHappening = computed(() => {
    return dayjs.utc(props.happening.start).isBefore(dayjs());
})

// ------------------------------------------------
// Modal Actions
// ------------------------------------------------
const editUserHappening = (happening) => {
    let editModal = useEditModal(happening);
    modal.open(editModal.view, editModal.content, editModal.payload, editModal.actions);
}

const confirmUserHappening = (happening) => {
    let confirmModal = useConfirmModal(happening);
    modal.open(confirmModal.view, confirmModal.content, confirmModal.payload, confirmModal.actions);
}

const deleteUserHappening = (happening) => {
    let deleteModal = useDeleteModal(happening);
    modal.open(deleteModal.view, deleteModal.content, deleteModal.payload, deleteModal.actions);
}
</script>
