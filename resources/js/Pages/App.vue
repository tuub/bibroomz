<template>
    <Head title="Home" />

    <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
    <x-modal />

    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
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
import UserHappenings from "../Components/UserHappenings.vue";
import Legend from "../Components/Legend.vue";

import XModal from "../Shared/XModal.vue";
import useModal from "../Stores/Modal";

import { useAuthStore } from "../Stores/AuthStore";
import { onMounted, onUnmounted, ref } from "vue";
import { storeToRefs } from "pinia";

import { Modal as FlowbiteModal } from "flowbite";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
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
    modal.init(
        new FlowbiteModal(
            document.getElementById("modal"),
            {
                closable: true,
                placement: "center",
                backdrop: "dynamic",
                backdropClasses:
                    "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
                onHide: () => {
                    modal.cleanup();
                },
            }
        )
    );
    // check auth session in backend
    authStore.check()
});
onUnmounted(() => {
    authStore.unsubscribe();
})
</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
