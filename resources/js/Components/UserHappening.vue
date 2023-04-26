<template>
    <div class="text-sm">
        <p>{{ happeningDate }}, {{ happeningStart }} - {{ happeningEnd }}</p>
        <p>Raum {{ happening.resource.title }}, {{ happening.resource.location }}</p>
        <p>Confirmer: {{ happening.confirmer }}</p>
        <button class="btn-xs bg-red-500 hover:bg-red-800 text-white" @click="deleteUserHappening(happening)">Delete</button>
        <button class="btn-xs bg-blue-500 hover:bg-blue-800 text-white" @click="editUserHappening(happening)">Edit</button>
    </div>
</template>

<script setup>

import {computed, ref} from "vue";
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
    happening: Object,
})

// ------------------------------------------------
// Computed
// ------------------------------------------------
const happeningDate = computed(() => {
    return dayjs(props.happening.start).utc().format('DD.MM.YYYY');
})

const happeningStart = computed(() => {
    return dayjs(props.happening.start).utc().format('HH:mm');
})

const happeningEnd = computed(() => {
    return dayjs(props.happening.end).utc().format('HH:mm');
})

// ------------------------------------------------
// Modal Actions
// ------------------------------------------------
const editUserHappening = (happening) => {
    console.log('DATA in editUserHappening modal definition:')
    console.log(happening)
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
                    /*
                    happeningStore.deleteHappening(happening.id).then((response) => {
                        console.log(response)
                        modal.close()
                    })
                     */
                    await happeningStore.deleteHappening(happening.id).then(() => {

                    })

                    //if (happeningStore.deleteHappening(happening.id)) {
                        //authStore.removeUserHappening(happening.id)
                    //    modal.close();
                    //}
                },
            }
        ],
    }
    modal.open(data.view, data.content, data.payload, data.actions)
}

</script>
