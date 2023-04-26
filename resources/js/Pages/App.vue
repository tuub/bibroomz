<template>
    <Head title="Home" />

    <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
    <x-modal />

    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
            <Legend></Legend>
            <div v-if="isAuthenticated">
                <UserEvents :events="userEvents"></UserEvents>
            </div>
            <div v-else>
                <LoginForm></LoginForm>
            </div>
        </div>
        <div id="calendar" class="basis-4/5 md:basis-4/5">
            <Calendar
                @show-status="showStatus"
                @open-modal-component="getModal">
            </Calendar>
        </div>
    </div>
</template>

<script setup>
import Calendar from "../Components/Calendar.vue";
import LoginForm from "../Components/LoginForm.vue";
import UserEvents from "../Components/UserEvents.vue"
import Legend from "../Components/Legend.vue";

import XModal from "../Shared/XModal.vue";
import useModal from "../Stores/Modal.ts";

import { useAuthStore } from '../Stores/AuthStore';
import {onMounted, ref, watch} from "vue";
import {useReservationStore} from "../Stores/ReservationStore";
import {storeToRefs} from "pinia";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const reservationStore = useReservationStore();
const modal = useModal();

let { isAuthenticated, userEvents } = storeToRefs(authStore)
let { doRefreshInterface } = storeToRefs(reservationStore)
// ------------------------------------------------
// Status message
// ------------------------------------------------
let statusMessage = ref('')
const showStatus = (status) => {
    statusMessage.value = status
}

// ------------------------------------------------
// Modal
// ------------------------------------------------
const getModal = (data) => {
    modal.open(data.view, data.content, data.payload, data.actions);
}

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(doRefreshInterface, () => {
    authStore.fetchUserEvents().then(() => {
        reservationStore.doRefreshInterface = false
    })
})
// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    // check auth session in backend
    authStore.check()
});


</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
