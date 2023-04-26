<template>
    <div id="status-legend">
        <LegendItem v-if="isAuthenticated" css-class="user-reservation" label="Von Ihnen vorgemerkt"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-confirmation" label="Von Ihnen zu bestätigen"></LegendItem>
        <LegendItem v-if="isAuthenticated" css-class="user-booking" label="Von Ihnen gebucht"></LegendItem>
        <LegendItem css-class="reservation" label="Vorgemerkt"></LegendItem>
        <LegendItem css-class="booking" label="Gebucht"></LegendItem>
        <LegendItem css-class="closing" label="Geschlossen"></LegendItem>
    </div>
</template>

<script setup>
import {useAuthStore} from "../Stores/AuthStore.js";
import LegendItem from "./LegendItem.vue";
import {storeToRefs} from "pinia";

const authStore = useAuthStore();
let { isAuthenticated } = storeToRefs(authStore)
</script>
