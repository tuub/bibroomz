<template>
    <div class="brand-wrapper block h-8 w-full">
        <a :href="route('start')" class="brand-name float-left">
            {{ appName }}
        </a>
        <div class="login-lang-wrapper">
            <LangSwitch />
            <CurrentLogin />
        </div>
    </div>
</template>

<script setup>
import CurrentLogin from "@/Shared/CurrentLogin.vue";
import LangSwitch from "@/Shared/LangSwitch.vue";
import { useAppStore } from "@/Stores/AppStore";

import { storeToRefs } from "pinia";

const appStore = useAppStore();
const { appName } = storeToRefs(appStore);
</script>
<style>
.brand-name {
    font-weight: 800;
    font-size: x-large;
}

.login-lang-wrapper {
    float: right;
    margin-top: 8px;
    width: 240px;
    height: 2em;
}

.login-lang-wrapper > div:nth-child(2) {
    float: right;
}
</style>
