<template>
    <div id="status-legend" class="">
        <template v-for="status in statuses">
            <Tag
                v-if="status.isDisplayed"
                :key="status.label"
                :value="status.label"
                :class="status.css"
                class="mx-0 my-0 rounded-none"
            >
                <div class="flex items-center gap-2 px-0">
                    <span class="text-xs font-normal">{{ status.label }}</span>
                </div>
            </Tag>
        </template>
    </div>

    <!--
    <div class="legend-item-toggle-button-wrapper">
        <button id="legend-toggle-button" class="legend-item-toggle-button" @click="toggle">
            <i v-if="isOpen" class="ri-arrow-right-double-line"></i>
            <i v-else class="ri-arrow-left-double-line"></i>
        </button>
        <Transition>
            <div v-show="isOpen" id="status-legend">
                <LegendItem
                    v-if="isAuthenticated"
                    css-class="user-reservation"
                    :label="$t('legend.user-reservation')"
                ></LegendItem>
                <LegendItem
                    v-if="isAuthenticated"
                    css-class="user-to-verify"
                    :label="$t('legend.user-to-verify')"
                ></LegendItem>
                <LegendItem
                    v-if="isAuthenticated"
                    css-class="user-booking"
                    :label="$t('legend.user-booking')"
                ></LegendItem>
                <LegendItem css-class="reservation" :label="$t('legend.reservation')"></LegendItem>
                <LegendItem css-class="booking" :label="$t('legend.booking')"></LegendItem>
                <LegendItem css-class="closing" :label="$t('legend.closing')"></LegendItem>
            </div>
        </Transition>
    </div>
    -->
</template>

<script setup>
import { useAuthStore } from "../../Stores/AuthStore.js";

import { trans } from "laravel-vue-i18n";
import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isAuthenticated } = storeToRefs(authStore);

const statuses = computed(() => [
    {
        id: "reservation",
        label: trans("legend.reservation"),
        css: "reservation",
        isDisplayed: true,
    },
    {
        id: "booking",
        label: trans("legend.booking"),
        css: "booking",
        isDisplayed: true,
    },
    {
        id: "closing",
        label: trans("legend.closing"),
        css: "closing",
        isDisplayed: true,
    },
    {
        id: "user-reservation",
        label: trans("legend.user-reservation"),
        css: "user-reservation",
        isDisplayed: isAuthenticated.value,
    },
    {
        id: "user-booking",
        label: trans("legend.user-booking"),
        css: "user-booking",
        isDisplayed: isAuthenticated.value,
    },
    {
        id: "user-to-verify",
        label: trans("legend.user-to-verify"),
        css: "user-to-verify",
        isDisplayed: isAuthenticated.value,
    },
]);
</script>
