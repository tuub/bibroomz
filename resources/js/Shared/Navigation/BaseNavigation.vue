<template>
    <nav class="w-full">
        <Brand />

        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">
            <a :href="institution?.home_uri" class="institution-logo-uri" target="_blank">
                <img :src="institution?.logo_uri" class="float-left h-7" :alt="translate(institution?.title)" />
            </a>
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
</script>

<style>
nav {
    z-index: 9999;
    box-shadow: 0 3px 3px rgba(204, 203, 203);
    background-color: white;
    padding: 30px;
    height: 7em;
    color: #c40d20;
    font-size: 17px;
}

#nav-footer-wrapper > button {
    display: none;
    float: right;
}

.institution-logo-uri {
    position: absolute;
}

@media only screen and (max-width: 1150px) {
    #nav-footer-wrapper > button {
        display: block;
    }
}
</style>
