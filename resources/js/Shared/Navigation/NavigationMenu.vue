<template>
    <div class="navigation-menu-wrapper">
        <button
            class="mobile-menu-button"
            type="button"
            aria-controls="mobile-menu"
            aria-expanded="false"
            @click="toggle"
        >
            <span class="sr-only">Open main menu</span>
            <svg
                class="mobile-button w-6 h-6"
                aria-hidden="true"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill-rule="evenodd"
                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                    clip-rule="evenodd"
                ></path>
            </svg>
        </button>

        <div
            id="mobile-menu"
            class="w-90p mt-3px float-right"
            :class="isVisible ? 'mobile-menu-visible' : 'mobile-menu-hidden'"
        >
            <slot></slot>
            <button
                class="mobile-menu-background"
                :class="isVisible ? 'mobile-menu-visible' : 'mobile-menu-hidden'"
                @click="toggle"
            ></button>
            <button
                class="mobile-menu-background-red"
                :class="isVisible ? 'mobile-menu-visible' : 'mobile-menu-hidden'"
                @click="toggle"
            ></button>
        </div>
    </div>
</template>
<script setup>
import { ref } from "vue";

defineProps({
    isAlwaysMobile: {
        type: Boolean,
        default: false,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isVisible = ref(false);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const toggle = () => {
    isVisible.value = !isVisible.value;
};
</script>

<style>
.mobile-menu-button {
    display: block;
}

#mobile-menu {
    position: absolute;
    top: 85px;
    right: 30px;
    width: 90%;
}

#mobile-menu > ul {
    float: right;
}

.mobile-button {
    position: absolute;
    top: 77px;
    right: 27px;
    display: block;
}

#mobile-menu > ul > a {
    position: absolute;
    display: flex;
    height: 40px;
    width: 280px;
    top: 25px;
    right: 0;
    background: #fdfdfd;
    padding: 8px;
    z-index: 25;
}

.mobile-menu-background-red {
    position: fixed;
    background-color: #c40d1e;
    top: 0px;
    right: 0px;
    width: 20em;
    height: 100%;
}

#mobile-menu > ul > a:nth-child(1) {
    top: -50px;
}

#mobile-menu > ul > a:nth-child(2) {
    top: 0px;
}

#mobile-menu > ul > a:nth-child(3) {
    top: 50px;
}

#mobile-menu > ul > a:nth-child(4) {
    top: 100px;
}

#mobile-menu > ul > a:nth-child(5) {
    top: 150px;
}

#mobile-menu > ul > a:nth-child(6) {
    top: 200px;
}

#mobile-menu > ul > a:nth-child(7) {
    top: 250px;
}

#mobile-menu > ul > a:nth-child(8) {
    top: 300px;
}

#mobile-menu > ul > a:nth-child(9) {
    top: 350px;
}

#mobile-menu > ul > a:nth-child(10) {
    top: 400px;
}

#mobile-menu > ul > a:nth-child(11) {
    top: 450px;
}

#mobile-menu > ul > a:nth-child(12) {
    top: 500px;
}

#mobile-menu > ul > a:nth-child(13) {
    top: 550px;
}

#mobile-menu > ul > a:nth-child(14) {
    top: 600px;
}

.mobile-menu-background {
    position: fixed;
    background-color: rgba(0, 0, 0, 50%);
    top: 0px;
    right: 0px;
    width: 100%;
    height: 100%;
}

.mobile-menu-hidden {
    display: none;
}

.mobile-menu-visible {
    display: block;
}
</style>
