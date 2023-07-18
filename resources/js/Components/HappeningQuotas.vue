<template>
    <HappeningQuota v-for="(value, type) in quotas" :type="type" :value="value" :setting="getQuotaSetting(type)"></HappeningQuota>
</template>

<script setup>
import {useAuthStore} from "@/Stores/AuthStore";
import {useAppStore} from "@/Stores/AppStore";
import HappeningQuota from "./HappeningQuota.vue";

const authStore = useAuthStore()
const appStore = useAppStore()

let quotas  = authStore.quotas
let institution = appStore.institution

console.log(institution)
console.log(quotas)

const getQuotaSetting = (type) => {
    return Number(institution.settings['quota_' + type])
}

</script>
