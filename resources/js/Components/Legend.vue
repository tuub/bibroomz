<template>
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
</template>

<script setup>
import { useAuthStore } from "../Stores/AuthStore.js";

import LegendItem from "./LegendItem.vue";

import { storeToRefs } from "pinia";
import { ref } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isAuthenticated } = storeToRefs(authStore);

const isOpen = ref(true);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const toggle = () => {
    isOpen.value = !isOpen.value;
};
</script>
<style>
#status-legend {
    position: fixed;
    top: 144px;
    right: 0px;
    z-index: 9;
    background: rgba(0, 0, 0, 0%);
    width: 195px;
    height: 168px;
}

#status-legend > span {
    display: block;
    float: left;
    padding: 7px;
    width: 100%;
}

.legend-item-toggle-button-wrapper {
    position: absolute;
}

.legend-item-toggle-button {
    position: fixed;
    top: 120px;
    right: 0px;
    rotate: unset;
    z-index: 9;
    transition:
        background 0.25s,
        color 0.25s;
    box-shadow: 0 3px 3px rgb(204, 203, 203);
    background: white;
    width: 40px;
    min-width: 2rem;
    height: 40px;
    color: #c40d1e;
    font-weight: 400;
    font-size: 1.5rem;
    font-family: Muli, sans-serif, Arial;
    text-align: center;
    text-decoration: none;
}

.v-enter-active,
.v-leave-active {
    transition: opacity 0.5s ease;
}

.v-enter-from,
.v-leave-to {
    opacity: 0;
}
</style>
