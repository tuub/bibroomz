<template>
    <div class="legend-item-toggle-button-wrapper">
        <button class="legend-item-toggle-button" @click="toggle">
            <i class="ri-question-line"></i>
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

const isOpen = ref(false);

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
    top: 235px;
    z-index: 9;
    background: rgba(0 0 0 / 0%);
    right: 0;
    height: 168px;
    width: 195px;
}

#status-legend > span {
    float: left;
    display: block;
    width: 100%;
    padding: 7px;
}

.legend-item-toggle-button-wrapper {
    position: absolute;
}

.legend-item-toggle-button {
    position: fixed;
    right: -2px;
    top: 211px;
    rotate: unset;
    height: 40px;
    width: 40px;
    z-index: 9;
    background: white;
    /* border: 0.0625rem solid #aaa; */
    color: #c40d1e;
    font-family: Muli, sans-serif, Arial;
    font-size: 1.5rem;
    font-weight: 400;
    min-width: 2rem;
    text-align: center;
    text-decoration: none;
    transition: background 0.25s, color 0.25s;
    box-shadow: 0 3px 3px rgb(204, 203, 203);
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
