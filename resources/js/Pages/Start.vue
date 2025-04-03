<template>
    <XModal />

    <div class="bg-surface-0 px-6 py-6 md:px-12 lg:px-20 dark:bg-surface-950">
        <div class="flex flex-col items-center gap-4 text-center text-surface-700 dark:text-surface-100">
            <div class="text-lg font-bold leading-tight text-tub">BibRoomz</div>
            <div class="text-4xl font-bold leading-tight text-surface-900 dark:text-surface-0">
                {{ $t("start.header") }}
            </div>
            <div class="text-xl leading-normal text-surface-700 dark:text-surface-100">
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
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
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

<script setup>
import InstitutionCard from "@/Components/InstitutionCard.vue";
import { useLogin } from "@/Composables/Login";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { onBeforeMount } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
    institutions: {
        type: Array,
        default: () => [],
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isAuthenticated } = storeToRefs(authStore);
const { loginUser } = useLogin();

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.appName = props.appName;
    appStore.resourceGroup = null;
    appStore.settings = null;
    appStore.hiddenDays = null;
});
</script>
