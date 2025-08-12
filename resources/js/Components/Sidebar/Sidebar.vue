<script setup>
import HappeningQuotas from "@/Components/HappeningQuotas.vue";
import SidebarBlock from "@/Components/Sidebar/SidebarBlock.vue";
import UserHappenings from "@/Components/UserHappenings.vue";
import { useLogin } from "@/Composables/Login";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    isMultiTenancy: {
        type: Boolean,
        default: false,
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
const can = authStore.can;
const { loginUser } = useLogin();
const { allowedResourceGroups, isAuthenticated, userHappenings } = storeToRefs(authStore);
const translate = appStore.translate;
const isAllowed = computed(() => {
    return allowedResourceGroups.value.includes(props.resourceGroup.id);
});
const helpURI = computed(() => {
    return props.resourceGroup.help_uri;
});

// ------------------------------------------------
// Functions
// ------------------------------------------------
</script>

<template>
    <h2 class="sr-only mb-2 block text-xl font-bold">
        {{ $t("sidebar.header") }}
    </h2>
    <div class="flex flex-col space-y-5">
        <SidebarBlock :title="$t('sidebar.about', { resource_group: translate(resourceGroup.title) })">
            <p class="mb-2 text-sm italic">
                {{ translate(resourceGroup.description) }}
                <a v-if="!isAllowed && helpURI" :href="helpURI" target="_blank" class="text-tub hover:underline">
                    {{ $t("sidebar.help") }}
                </a>
            </p>
        </SidebarBlock>

        <SidebarBlock v-if="!isAuthenticated" :title="$t('sidebar.login.header')">
            <div class="text-center">
                <p class="mb-5 block">
                    {{ $t("sidebar.login.description") }}
                </p>
                <Button
                    size="small"
                    icon="pi pi-user"
                    :label="$t('sidebar.login.label')"
                    class="rounded bg-tub px-3 text-white"
                    @click="loginUser"
                />
            </div>
        </SidebarBlock>

        <SidebarBlock v-if="isAuthenticated && !can('unlimited_quotas')" :title="$t('sidebar.quota.header')">
            <HappeningQuotas :happenings="userHappenings"></HappeningQuotas>
        </SidebarBlock>

        <template v-if="isAuthenticated">
            <UserHappenings :happenings="userHappenings"></UserHappenings>
        </template>
    </div>
</template>
