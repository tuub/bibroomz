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

        <div id="mobile-menu" class="w-90p mt-3px float-right" :class="isVisible ? 'mobile-menu-visible' : 'mobile-menu-hidden'">
            <slot></slot>
        </div>
    </div>
</template>
<script setup>
import { ref } from "vue";

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
.w-70p {
    width: 70%;
}
.w-90p {
    width: 90%;
}

.mt-3px {
    margin-top: -3px;
}

/* .navigation-menu-wrapper { */

/* } */

.mobile-menu-button {
    display: block;
}
#mobile-menu > ul{
    float: right;
}
#mobile-menu > ul > li {
    float: left;
    margin-left: 20px;
}

.mobile-button{
    position: absolute;
    top: 77px;
    right: 27px;
    display: none;
}
@media only screen and (max-width: 1150px) {
    .mobile-button {
        position: absolute;
        display: block;
    }
    #mobile-menu > ul > li {
        position: absolute;
        height: 40px;
        width: 190px;
        top: 25px;
        right: 0px;
        background: #FFFFFF;
        padding: 8px;
        z-index: 25;
    }
    #mobile-menu > ul > li:nth-child(1){
        top: 25px;
    }
    #mobile-menu > ul > li:nth-child(2){
        top: 65px;
    }
    #mobile-menu > ul > li:nth-child(3){
        top: 105px;
    }
    #mobile-menu > ul > li:nth-child(4){
        top: 145px;
    }
    #mobile-menu > ul > li:nth-child(5){
        top: 185px;
    }
    #mobile-menu > ul > li:nth-child(6){
        top: 225px;    
    }
    #mobile-menu > ul > li:nth-child(7){
        top: 265px;    
    }
    
    .mobile-menu-hidden {
        display: none;
    }
    .mobile-menu-visible {
        display: block;
    }
}
</style>
