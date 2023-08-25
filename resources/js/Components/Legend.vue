<template>
    <div id="status-legend">
        <LegendItem v-if="isAuthenticated" css-class="user-reservation" :label="$t('legend.user-reservation')"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-to-verify" :label="$t('legend.user-to-verify')"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-booking" :label="$t('legend.user-booking')"></LegendItem>
        <LegendItem css-class="reservation" :label="$t('legend.reservation')"></LegendItem>
        <LegendItem css-class="booking" :label="$t('legend.booking')"></LegendItem>
        <LegendItem css-class="closing" :label="$t('legend.closing')"></LegendItem>
    </div>
</template>

<script setup>
import {useAuthStore} from "../Stores/AuthStore.js";
import LegendItem from "./LegendItem.vue";
import {storeToRefs} from "pinia";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let { isAuthenticated } = storeToRefs(authStore)
</script>
