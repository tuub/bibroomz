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
.w-20p {
    width: 20%;
}

nav {
    background-color: white;
    color: #c40d20;
    padding: 30px;
    height: 7.5em;
}


@media only screen and (max-width: 1150px) {
    .login-lang-wrapper {
        width: 40px;
    }
}
</style>
