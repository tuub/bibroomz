<template>
    <div>
        <PageHead :title="institutionTitle" />

        <div v-if="statusMessage" class="border bg-green-500" v-text="statusMessage" />
        <XModal />

        <div id="calendar_sidebar_wrapper" class="">
            <div id="calendar" class="basis-4/5 md:basis-4/5">
                <Calendar @show-status="showStatus" @open-modal-component="getModal"> </Calendar>
            </div>
            <div id="sidebar" class="basis-1/5 md:basis-1/5">
                <div v-if="isAuthenticated">
                    <UserHappenings :happenings="userHappenings"></UserHappenings>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Calendar from "@/Components/Calendar.vue";
import UserHappenings from "@/Components/UserHappenings.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import useModal from "@/Stores/Modal";

import { Modal as FlowbiteModal } from "flowbite";
import { storeToRefs } from "pinia";
import { onBeforeMount, onMounted, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    institution: {
        type: Object,
        required: true,
    },
    isMultiTenancy: {
        type: Boolean,
        default: false,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();
const statusMessage = ref("");
const { institutionTitle } = storeToRefs(appStore);
const { isAuthenticated, userHappenings } = storeToRefs(authStore);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const showStatus = (status) => {
    statusMessage.value = status;
};

const getModal = (data) => {
    modal.open(data.view, data.content, data.payload, data.actions);
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrentInstitution(props.institution, props.isMultiTenancy);
});

onMounted(() => {
    modal.init(
        new FlowbiteModal(document.getElementById("modal"), {
            closable: true,
            placement: "center",
            backdrop: "dynamic",
            backdropClasses: "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                modal.cleanup();
            },
        }),
    );
});
</script>

<style scoped>
#sidebar {
    margin-top: 30px;
}
</style>
