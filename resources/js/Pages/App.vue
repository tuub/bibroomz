<template>
    <Head title="Home" />

    <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
    <x-modal />

    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
            <Legend></Legend>
            <div v-if="isAuthenticated">
                <UserReservations></UserReservations>
            </div>
            <div v-else>
                <LoginForm></LoginForm>
            </div>
        </div>
        <div id="calendar" class="basis-4/5 md:basis-4/5">
            <Calendar
                @show-status="showStatus"
                @open-modal-component="handleOpenComponentModal">
            </Calendar>
        </div>
    </div>
</template>

<script setup>
import Calendar from "../Components/Calendar.vue";
import LoginForm from "../Components/LoginForm.vue";
import UserReservations from "../Components/UserReservations.vue"
import Legend from "../Components/Legend.vue";

import XModal from "../Shared/XModal.vue";
import useModal from "../Stores/Modal.ts";

import { useAuthStore } from '../Stores/AuthStore';
import {onMounted, ref, watch} from "vue";
import {useReservationStore} from "../Stores/ReservationStore";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const reservationStore = useReservationStore();
const modal = useModal();

let currentUser = ref('')
let isAuthenticated = ref(authStore.isAuthenticated);

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
const handleOpenComponentModal = (data) => {
    console.log(data)
    modal.open(data.view, data.content, data.payload, data.actions);
}

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(
    () => authStore.isAuthenticated,
    () => {
        console.log('Updated component after auth change: App')
        isAuthenticated.value = authStore.isAuthenticated
        currentUser.value = authStore.user ?? '';
    },
)

// FIXME: Not always called => if no error occurs, modal closes without this (even if I assigned true to it in addReservation)
watch(
    () => reservationStore.isModalOpSuccessful,
    () => {
        console.log('Closing modal after successful OP')
        modal.close()
    },
)
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
