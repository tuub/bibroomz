<template>
    <nav class="w-full">
        <Brand />

        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">
            <a :href="institution?.home_uri" class="float-left" target="_blank">
                <img :src="institution?.logo_uri" class="float-left h-7" :alt="institution?.title" />
            </a>
            <span class="ml-2">{{ translate(resourceGroup?.name) }}</span>
            <slot />
        </div>
    </nav>
</template>

<script setup>
import Brand from "@/Shared/Brand.vue";
import { useAppStore } from "@/Stores/AppStore";

const appStore = useAppStore();
const translate = appStore.translate;
const institution = appStore.institution;
const resourceGroup = appStore.resourceGroup;
</script>

<style>
nav {
    background-color: white;
    color: #C40D20;
    padding: 30px;
    height: 7.5em;
    z-index: 9999;
    box-shadow: 0 3px 3px rgba(204, 203, 203);
    font-size: 17px;
}

#nav-footer-wrapper > button {
    float: right;
    display: none;
}

@media only screen and (max-width: 1150px) {
    #nav-footer-wrapper > button {
        display: block;
    }

    nav {
        background-color: white;
        color: #C40D20;
        padding: 30px;
        height: 7em;
    }
}
</style>
