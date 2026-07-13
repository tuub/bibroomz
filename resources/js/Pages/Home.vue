<template>
    <div id="calendar" class="space-y-4">
        <SystemNotificationList :notifications="systemNotifications" />
        <h1 class="sr-only mb-2 block text-xl font-bold">{{ $t("calendar.header") }}</h1>
        <Calendar @open-modal-component="getModal"></Calendar>
    </div>

    <Teleport defer to="aside#sidebar">
        <Sidebar :resource-group="resourceGroup"></Sidebar>
    </Teleport>
</template>

<script setup lang="ts">
import Calendar from "@/Components/Calendar/Calendar.vue";
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import SystemNotificationList from "@/Components/SystemNotificationList.vue";
import CalendarLayout from "@/Layouts/CalendarLayout.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { ResourceGroup, Settings } from "@/Stores/AppStore";
import type { ModalOpenPayload } from "@/Stores/Modal";
import useModal from "@/Stores/Modal";

import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

defineOptions({
    layout: CalendarLayout,
});

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        resourceGroup: ResourceGroup;
        settings: Settings;
        hiddenDays: number[];
        isMultiTenancy?: boolean;
    }>(),
    {
        isMultiTenancy: false,
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const { systemNotifications } = storeToRefs(appStore);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getModal = (data: ModalOpenPayload) => {
    modal.open(data.view, data.content, data.payload, data.actions);
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, props.isMultiTenancy);
});
</script>
