<script setup lang="ts">
import InstitutionCard from "@/Components/InstitutionCard.vue";
import SystemNotificationList from "@/Components/SystemNotificationList.vue";
import { useLogin } from "@/Composables/Login";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { Institution } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { Head } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        appName: string;
        institutions?: Institution[];
    }>(),
    {
        institutions: () => [],
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isAuthenticated } = storeToRefs(authStore);
const { globalNotifications } = storeToRefs(appStore);
const { loginUser } = useLogin();

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setStartPageContext(props.appName);
});
</script>
<template>
    <Head :title="'Start :: ' + appName" />

    <div v-if="globalNotifications.length" class="mb-4">
        <SystemNotificationList :notifications="globalNotifications" />
    </div>

    <div class="bg-white px-6 py-6 md:px-12 lg:px-20 dark:bg-black">
        <div class="flex flex-col items-center gap-4 text-center text-black dark:text-white">
            <div class="text-lg font-bold leading-tight text-tub">BibRoomz</div>
            <div class="text-4xl font-bold leading-tight text-black dark:text-white">
                {{ $t("start.header") }}
            </div>
            <div class="text-xl leading-normal text-black dark:text-white">
                {{ $t("start.teaser") }}
            </div>
            <div>
                <ExternalLink
                    :href="$t('start.help.uri')"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                >
                    {{ $t("start.help.label") }}
                </ExternalLink>
                <a
                    v-if="!isAuthenticated"
                    href="#"
                    data-testid="start-login-link"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    aria-haspopup="dialog"
                    @click="loginUser"
                >
                    {{ $t("start.login") }}
                </a>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap justify-center">
        <InstitutionCard v-for="institution in institutions" :key="institution.id" :institution="institution" />
    </div>
</template>
