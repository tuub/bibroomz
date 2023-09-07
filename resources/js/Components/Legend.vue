<template>
    <div class="legend-item-toggle-button-wrapper">
        <button class="legend-item-toggle-button" @click="toggle">legend</button>
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
    top: 225px;
    z-index: 40;
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
    right: -19px;
    top: 208px;
    rotate: unset;
    height: 33px;
    width: 89px;
    z-index: 10;
    background: white;
    border: 0.0625rem solid #c40d1e;
    color: #c40d1e;
    font-family: Muli, sans-serif, Arial;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.125rem;
    margin: 0;
    min-width: 10rem;
    padding: 0.375rem 1.5625rem 0.5rem;
    text-align: center;
    text-decoration: none;
    transition:
        background 0.25s,
        color 0.25s;
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
