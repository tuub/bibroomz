<script setup lang="ts">
import InstitutionCard from "@/Components/InstitutionCard.vue";
import SystemNotificationList from "@/Components/SystemNotificationList.vue";
import { useLogin } from "@/Composables/Login";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { Institution } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

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

    <div class="bg-app-surface dark:bg-app-page px-6 py-6 md:px-12 lg:px-20">
        <div class="text-app-text dark:text-app-text flex flex-col items-center gap-4 text-center">
            <div class="text-tub text-lg leading-tight font-bold">BibRoomz</div>
            <div class="text-app-text dark:text-app-text text-4xl leading-tight font-bold">
                {{ $t("start.header") }}
            </div>
            <div class="text-app-text dark:text-app-text text-xl leading-normal">
                {{ $t("start.teaser") }}
            </div>
            <div>
                <ExternalLink
                    :href="$t('start.help.uri')"
                    class="text-tub hover:bg-tub hover:text-brand-contrast block rounded-sm px-3 py-2"
                >
                    {{ $t("start.help.label") }}
                </ExternalLink>
                <a
                    v-if="!isAuthenticated"
                    href="#"
                    data-testid="start-login-link"
                    class="text-tub hover:bg-tub hover:text-brand-contrast block rounded-sm px-3 py-2"
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
