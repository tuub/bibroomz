<template>
    <XModal />

    <div id="calendar">
        <h1 class="sr-only mb-2 block text-xl font-bold">{{ $t("calendar.header") }}</h1>
        <Calendar @open-modal-component="getModal"> </Calendar>
    </div>

    <Teleport defer to="aside#sidebar">
        <Sidebar :resource-group="resourceGroup"></Sidebar>
    </Teleport>
</template>

<script>
import CalendarLayout from "@/Layouts/CalendarLayout.vue";

export default {
    layout: CalendarLayout,
};
</script>

<script setup>
import { onBeforeMount } from "vue";
import { useAppStore } from "@/Stores/AppStore";
import useModal from "@/Stores/Modal";
import Calendar from "@/Components/Calendar/Calendar.vue";
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import XModal from "@/Shared/XModal.vue";

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

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();

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
