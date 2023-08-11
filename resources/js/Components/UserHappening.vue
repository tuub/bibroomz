<template>
    <div class="flex items-center space-x-4">
        <div class="flex-1 min-w-0"  :class="isPastHappening ? 'text-gray-400' : 'text-gray-900'">
            <span v-if="isPastHappening"
                class="text-xs font-normal inline-block py-0.5 px-2 uppercase rounded text-black bg-gray-200 uppercase last:mr-0 mr-1">
                {{ $t('user_happening.past_happening') }}
            </span>
            <p class="font-bold truncate dark:text-white pb-1">
                <i class="ri-calendar-event-line" :title="$t('user_happening.date_time')"></i>
                {{ happeningDate }}
                <i class="ri-arrow-right-line"></i>
                {{ happeningStart }} - {{ happeningEnd }}
            </p>
            <p class="text-sm font-medium pb-1">
                <i class="ri-home-line" :title="$t('user_happening.resource')"></i>
                {{ happening.resource.title }}
            </p>
            <p class="text-sm font-medium truncate pb-1">
                <i class="ri-map-pin-fill" :title="$t('user_happening.location')"></i>
                {{ happening.resource.location }}
            </p>
            <p class="text-sm font-medium pb-1">
                <i class="ri-user-fill" :title="$t('user_happening.user_01')"></i>
                {{ happening.user_01 }}
            </p>
            <p v-if="happening.isVerificationRequired" class="text-sm font-medium pb-1">
                <i class="ri-user-follow-fill" :title="$t('user_happening.user_02')"></i>
                {{ happening.user_02 }}
                <span v-if="!isPastHappening && happening.isVerified"
                      class="text-xs font-normal inline-block py-0.5 px-2 uppercase rounded text-black bg-green-300 uppercase last:mr-0 mr-1">
                    <i class="ri-check-line"></i>
                    {{ $t('user_happening.verified') }}
                </span>
            </p>
        </div>
        <!-- FIXME: do this in policy! -->
        <div class="text-base font-bold text-gray-900">
            <p>
                <a v-if="happening.can.delete"
                   @click="deleteUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-red-200 border border-gray-200 rounded-lg hover:bg-red-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-delete-bin-line mr-1"></i>
                    {{ $t('user_happening.form.delete') }}
                </a>
            </p>
            <p>
                <a v-if="happening.can.verify"
                   @click="verifyUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-check-double-line mr-1"></i>
                    {{ $t('user_happening.form.verify') }}
                </a>
            </p>
            <p>
                <a v-if="happening.can.edit"
                   @click="editUserHappening(happening)"
                   href="#"
                   class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                    <i class="ri-edit-line mr-1"></i>
                    {{ $t('user_happening.form.edit') }}
                </a>
            </p>
        </div>
    </div>
</template>

<script setup>
import {computed} from "vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import useModal from "../Stores/Modal";
import { useVerifyModal, useDeleteModal, useEditModal } from "@/Composables/ModalActions";

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

const verifyUserHappening = (happening) => {
    let verifyModal = useVerifyModal(happening);
    modal.open(verifyModal.view, verifyModal.content, verifyModal.payload, verifyModal.actions);
}

const deleteUserHappening = (happening) => {
    let deleteModal = useDeleteModal(happening);
    modal.open(deleteModal.view, deleteModal.content, deleteModal.payload, deleteModal.actions);
}
</script>
