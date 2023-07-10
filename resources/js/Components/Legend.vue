<template>
    <div id="status-legend">
        <LegendItem v-if="isAuthenticated" css-class="user-reservation" label="Von Ihnen vorgemerkt"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-to-confirm" label="Von Ihnen zu bestätigen"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-booking" label="Von Ihnen gebucht"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-confirmed" label="Von Ihnen bestätigt"></LegendItem>
        <LegendItem css-class="reservation" label="Vorgemerkt"></LegendItem>
        <LegendItem css-class="booking" label="Gebucht"></LegendItem>
        <LegendItem css-class="closing" label="Geschlossen"></LegendItem>
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
