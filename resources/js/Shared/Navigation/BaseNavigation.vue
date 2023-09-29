<template>
    <nav class="w-full">
        <Brand />

        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">

            <a :href="institutionHomeUri" class="float-left" target="_blank">
            <img :src="institutionLogoUri" class="float-left h-7" :alt="institutionTitle" />
            </a>
            <slot />
        </div>
    </nav>
</template>

<script setup>
import Brand from "@/Shared/Brand.vue";
import { storeToRefs } from "pinia";
import { useAppStore } from "@/Stores/AppStore";
const appStore = useAppStore();
const {institutionHomeUri, institutionLogoUri, institutionTitle } = storeToRefs(appStore);
</script>

<style>

nav {
    background-color: white;
    color: #C40D20;
    padding: 30px;
    height: 7.5em;
    z-index: 9999;
    box-shadow: 0 3px 3px rgba(204,203,203);
    font-size: 17px;
}

#nav-footer-wrapper > button{
    float: right;
    display: none;
}

@media only screen and (max-width: 1150px) {
    #nav-footer-wrapper > button{
        display: block;
    }

    nav{
        background-color: white;
        color: #C40D20;
        padding: 30px;
        height: 7em;
    }

    .login-lang-wrapper {
        width: 40px;
    }

}
</style>
