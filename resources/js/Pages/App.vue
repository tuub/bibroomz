<template>
    <Head title="Home" />

    <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
    <x-modal />

    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
            <Legend></Legend>
            <div v-if="isAuthenticated">
                <UserHappenings :happenings="userHappenings"></UserHappenings>
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
import UserHappenings from "../Components/UserHappenings.vue"
import Legend from "../Components/Legend.vue";

import XModal from "../Shared/XModal.vue";
import useModal from "../Stores/Modal.ts";

import { useAuthStore } from '../Stores/AuthStore';
import {onMounted, ref, watch} from "vue";
import {useHappeningStore} from "../Stores/HappeningStore";
import {storeToRefs} from "pinia";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const happeningStore = useHappeningStore();
const modal = useModal();

let { isAuthenticated, userHappenings } = storeToRefs(authStore)

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
// Mount
// ------------------------------------------------
onMounted(() => {
    // check auth session in backend
    authStore.check()

    Echo.channel('happenings').listen('HappeningDeleted',(e)=>{
        authStore.removeUserHappening(e.id)
        happeningStore.doRefreshCalendar = true
    })

    Echo.channel('happenings').listen('HappeningCreated',(e)=>{
        authStore.addUserHappening(e.happening)
        happeningStore.doRefreshCalendar = true
    })

    Echo.channel('happenings').listen('HappeningUpdated',(e)=>{
        authStore.updateUserHappening(e.happening)
        happeningStore.doRefreshCalendar = true
    })
});


</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
