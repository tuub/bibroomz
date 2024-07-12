<template>
    <div>
        <PageHead :title="translate(appStore.resourceGroup.title) + ' - ' + translate(appStore.institution.title)" />

        <XModal />

        <div id="calendar-sidebar-wrapper" class="">
            <div
                id="calendar"
                class="calendar-wrapper basis-4/5 md:basis-4/5"
                :class="{ 'calendar-if-not-logged-in': !isAuthenticated }"
            >
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

import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

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
</script>

<style scoped>
#sidebar {
    float: right;
    margin-top: 24px;
    margin-bottom: 30px;
    width: 26%;
}

#calendar-sidebar-wrapper {
    display: block;
    margin-top: 13.5em;
    width: 100%;
}

.calendar-wrapper {
    display: inline-block;
    position: sticky;
    width: 72%;
}

@media only screen and (max-width: 1150px) {
    #sidebar {
        display: contents;
        float: left;
        margin-top: 30px;
        margin-bottom: 30px;
        width: 100%;
    }

    .calendar-wrapper {
        display: block;
        width: 100%;
    }
}
</style>
