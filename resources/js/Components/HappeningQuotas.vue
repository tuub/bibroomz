<template>
    <HappeningQuota
        v-for="(value, type) in quotas"
        :key="type"
        :type="type"
        :value="value"
        :setting="getQuotaSetting(type)"
    ></HappeningQuota>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import { useAppStore } from "@/Stores/AppStore";
import HappeningQuota from "./HappeningQuota.vue";
import { computed } from "vue";

const authStore = useAuthStore();
const appStore = useAppStore();

const institution = appStore.institution;

const getQuotaSetting = (type) => {
    return Number(institution.settings["quota_" + type]);
};

const quotas = computed(() =>
    Object.fromEntries(Object.entries({ ...authStore.quotas }).filter(([key]) => getQuotaSetting(key) > 0))
);
</script>
