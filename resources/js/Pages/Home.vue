<template>
    <div>
        <PageHead :title="translate(appStore.institution.title)" />

        <XModal />

        <div id="calendar-sidebar-wrapper" class="">
            <div id="calendar" class="calendar-wrapper basis-4/5 md:basis-4/5" :class="{ 'calendar-if-not-logged-in': !isAuthenticated}" >
                <Calendar @open-modal-component="getModal"> </Calendar>
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
import {onBeforeMount, onMounted} from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: true,
    },
    hiddenDays: {
        type: Array,
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
const { isAuthenticated, userHappenings } = storeToRefs(authStore);
const translate = appStore.translate;

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getModal = (data) => {
    modal.open(data.view, data.content, data.payload, data.actions);
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, props.isMultiTenancy);
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
    margin-top: 24px;
    margin-bottom: 30px;
    width: 26%;
    float: right;
}

#calendar-sidebar-wrapper {
    display: block;
    width: 100%;
}

.calendar-wrapper {
    display: inline-block;
    width: 72%;
}

@media only screen and (max-width: 1150px) {
    #sidebar {
        margin-top: 30px;
        margin-bottom: 30px;
        display: contents;
        width: 100%;
        float: left;
    }

    .calendar-wrapper {
        display: block;
        width: 100%;
    }


}
</style>
