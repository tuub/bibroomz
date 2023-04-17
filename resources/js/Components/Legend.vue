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
import {ref, watch} from "vue";
import LegendItem from "./LegendItem.vue";

const authStore = useAuthStore();
let isAuthenticated = ref(authStore.isAuthenticated);

watch(
    () => authStore.isAuthenticated,
    () => {
        console.log('Updated component after auth change: Legend')
        isAuthenticated.value = authStore.isAuthenticated
    },
)

</script>
