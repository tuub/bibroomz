<template>
    <div class="flex items-center space-x-4">
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-900 truncate dark:text-white pb-1">
                <i class="ri-calendar-event-line"></i>
                {{ happeningDate }}
                <i class="ri-arrow-right-line"></i>
                {{ happeningStart }} - {{ happeningEnd }}
            </p>
            <p class="text-sm font-medium text-gray-900 dark:text-white pb-1">
                <i class="ri-home-line"></i>
                {{ happening.resource.title }}
            </p>
            <p class="text-sm font-medium text-gray-900 truncate dark:text-white pb-1">
                <i class="ri-map-pin-fill"></i>
                {{ happening.resource.location }}
            </p>
            <p class="text-sm font-medium text-gray-900 dark:text-white pb-1">
                <i class="ri-user-follow-fill"></i>
                {{ happening.confirmer }}
                <i class="ri-check-double-line"></i>
            </p>
        </div>
        <div class="text-base font-bold text-gray-900 dark:text-white">
            <p><a @click="deleteUserHappening(happening)"
                  href="#"
               class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-red-200 border border-gray-200 rounded-lg hover:bg-red-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                <i class="ri-delete-bin-line mr-1"></i>
                Delete
            </a></p>
            <p>
            <a @click="editUserHappening(happening)"
               href="#"
               class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700 mb-2">
                <i class="ri-edit-line mr-1"></i>
                Edit
            </a></p>
        </div>
    </div>
</template>

<script setup>

import {computed} from "vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import {useHappeningStore} from "../Stores/HappeningStore"
import EditHappening from "./Modals/EditHappening.vue";
import DeleteHappening from "./Modals/DeleteHappening.vue";
import useModal from "../Stores/Modal";
import {useAuthStore} from "../Stores/AuthStore";

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore()
const happeningStore = useHappeningStore()
const modal = useModal()

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits([
    'open-modal-component'
])

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    index: Number,
    happening: Object,
})

// ------------------------------------------------
// Computed
// ------------------------------------------------
const happeningDate = computed(() => {
    return dayjs.utc(props.happening.start).format('DD.MM.YYYY');
})

const happeningStart = computed(() => {
    return dayjs.utc(props.happening.start).format('HH:mm');
})

const happeningEnd = computed(() => {
    return dayjs.utc(props.happening.end).format('HH:mm');
})

// ------------------------------------------------
// Modal Actions
// ------------------------------------------------
const editUserHappening = (happening) => {
    let data = {
        view: EditHappening,
        content: {
            title: 'Edit',
            description: 'Edit your reservation here',
        },
        payload: happening,
        actions: [
            {
                label: 'Update',
                callback: (happening) => {
                    if (happeningStore.editHappening(happening)) {
                        modal.close();
                    }
                },
            }
        ],
    }
    modal.open(data.view, data.content, data.payload, data.actions)
}

const deleteUserHappening = (happening) => {
    let data = {
        view: DeleteHappening,
        content: {
            title: 'Confirm Delete',
            description: 'Are you sure you wanna delete your future?',
        },
        payload: happening,
        actions: [
            {
                label: 'Yes, delete',
                callback: async (happening) => {
                    await happeningStore.deleteHappening(happening.id)
                },
            }
        ],
    }
    modal.open(data.view, data.content, data.payload, data.actions)
}

</script>
