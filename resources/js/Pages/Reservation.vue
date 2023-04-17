<template>
    <form @submit.prevent="addReservation">
        <input v-model="reservationForm.resource" type="text" class="input w-full max-w-xs" name="resource" id="resource" />
        <input v-model="reservationForm.start" type="text" class="input w-full max-w-xs" name="start" id="start" />
        <input v-model="reservationForm.end" type="text" class="input w-full max-w-xs" name="end" id="end" />
        <input v-model="reservationForm.confirmer" type="text" class="input w-full max-w-xs" name="confirmer" id="confirmer" />
        <button class="btn bg-green-500 text-white font-bold p-4">ADD</button>
    </form>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import {reactive} from "vue";
import {useReservationStore} from "../Stores/ReservationStore";
import {storeToRefs} from "pinia";

/*
Get the current reservation from the reservation store. We had to use the pinia store since
the propagation via props was not possible due to lack of experience on our side or lack
of reactivity of the variables.
 */
const reservationStore = useReservationStore()
const { resource, resource_id, resource_title, start, end } = storeToRefs(reservationStore)

// ------------------------------------------------
// Props
// ------------------------------------------------

const props = defineProps({
    showModal: Boolean,
});

// ------------------------------------------------
// Emits
// ------------------------------------------------

const emit = defineEmits(['hideModal', 'refetch-events'])

const hideModal = () => {
    emit('hideModal')
}

// ------------------------------------------------
// API
// ------------------------------------------------

let apiStatus = reactive({
    code: '',
    message: '',
})

let reservationForm = useForm({
    resource: resource.id,
    start: start,
    end: end,
    confirmer: 'TODO',
});


const addReservation = () => {
    console.log('*** ADD RESERVATION ***');

    let createReservation = {
        resource: reservationForm.resource,
        start: reservationForm.start,
        end: reservationForm.end,
        confirmer: reservationForm.confirmer,
    }
    console.log('Adding reservation to backend ...')
    console.log(createReservation)

    axios.post('/reservations/add', createReservation)
        .then((response) => {
            console.log('API response:')
            console.log(response)
            console.log(response.status)
            console.log(response.data)
            apiStatus.code = response.status.toString()
            apiStatus.message = response.data
            emit('refetch-events')
            console.log('Done.')
        })
        .catch((error) => {
            if (error.response) {
                console.log('API Error:');
                apiStatus.code = error.response.status.toString()
                apiStatus.message = error.response.data.message
                console.log(apiStatus);
            }
        })
}
</script>
