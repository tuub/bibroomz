<template>
    <div class="brand-wrapper block w-full h-8">
        <a :href="route('start')" class="float-left brand-name">
            {{ appName }}
        </a>
        <div class="login-lang-wrapper">
                <LangSwitch />
                <CurrentLogin />
                
        </div>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { storeToRefs } from "pinia";
import CurrentLogin from "@/Shared/CurrentLogin.vue";
import LangSwitch from "@/Shared/LangSwitch.vue";

const appStore = useAppStore();
const { appName, institutionHomeUri, institutionLogoUri, institutionTitle } = storeToRefs(appStore);
</script>
<style>
.brand-name {
    font-size: x-large;
    font-weight: 800;
}
.login-lang-wrapper {
    display: inline-block;
    height: 2em;
    float: right;
    margin-top: 10px;
    width: 240px;
}
.login-lang-wrapper > div:nth-child(2) {
    float: right;
}

</style>
