<template>
    <PageHead :title="institutionTitle" />

    <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
    <x-modal />

    <div id="calendar_sidebar_wrapper" class="">
        <div id="calendar" class="basis-4/5 md:basis-4/5">
            <Calendar
                @show-status="showStatus"
                @open-modal-component="getModal">
            </Calendar>
        </div>
        <div id="sidebar" class="basis-1/5 md:basis-1/5">

            <div v-if="isAuthenticated">
                <UserHappenings :happenings="userHappenings"></UserHappenings>
            </div>
            <div v-else>
                <LoginForm></LoginForm>
            </div>
        </div>
    </div>
</template>

<script setup>
import Calendar from "@/Components/Calendar.vue";
import LoginForm from "@/Components/LoginForm.vue";
import UserHappenings from "@/Components/UserHappenings.vue";

import XModal from "@/Shared/XModal.vue";
import useModal from "@/Stores/Modal";

import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { onBeforeMount, onMounted, onUnmounted, ref } from "vue";
import { storeToRefs } from "pinia";

import { Modal as FlowbiteModal } from "flowbite";
import PageHead from "@/Shared/PageHead.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    institution: Object,
    is_multi_tenancy: Boolean,
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();
let statusMessage = ref('')
let { institutionTitle } = storeToRefs(appStore)
let { isAuthenticated, userHappenings } = storeToRefs(authStore)

// ------------------------------------------------
// Methods
// ------------------------------------------------
const showStatus = (status) => {
    statusMessage.value = status
}

const getModal = (data) => {
    modal.open(data.view, data.content, data.payload, data.actions);
}

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(()  => {
    appStore.setCurrentInstitution(props.institution, props.is_multi_tenancy)
})

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
    margin: 30px;
}
</style>
