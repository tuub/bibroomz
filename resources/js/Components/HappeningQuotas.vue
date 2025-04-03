<template>
    <ul>
        <li v-for="(value, type) in quotas" :key="type">
            <HappeningQuota :type="type" :value="value" :setting="getQuotaSetting(type)"></HappeningQuota>
        </li>
    </ul>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import HappeningQuota from "./HappeningQuota.vue";

import { computed } from "vue";

const authStore = useAuthStore();
const appStore = useAppStore();

const getQuotaSetting = (type) => {
    let setting = appStore.resourceGroup.settings.find((x) => x.key === "quota_" + type);
    return Number(setting["value"]);
};

const quotas = computed(() =>
    Object.fromEntries(Object.entries({ ...authStore.quotas }).filter(([key]) => getQuotaSetting(key) > 0)),
);
</script>
