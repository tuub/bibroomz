<template>
    <Modal
        title-text="Add Reservation"
        submit-text="Save"
        id="reservation-modal"
        cancel-text="Cancel"
        :modal-status="modalStatus"
        @submit-form="addReservation">

        <ReservationInfo></ReservationInfo>
        <UserInfo></UserInfo>

    </Modal>

    <!--
    <teleport to="body">
        <div class="modalContent">
            <h1>BLA BLUBB</h1>
            lorem upsum
        </div>
    </teleport>
    -->
</template>

<script setup>
import {inject, ref} from "vue";
import Modal from "./Modal2.vue";
import ReservationInfo from './ReservationInfo.vue';
import UserInfo from './UserInfo.vue';

// ------------------------------------------------
// Event Bus
// ------------------------------------------------
const emitter = inject('emitter');

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    calendarApi: Object,
    titleText: String,
    reservationData: Object,
    userData: Object,
})

let modalStatus = ref('');

const addReservation = () => {
    console.log('ADD RESERVATION!');
    if (true) {
        modalStatus.value = 'Reservation successfull.'
    }
}

</script>

