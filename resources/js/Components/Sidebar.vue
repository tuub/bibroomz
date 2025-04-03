<template>
    <h1 class="sr-only mb-2 block text-xl font-bold">
        {{ $t("sidebar.header") }}
    </h1>
    <h2 class="mb-2 block text-sm font-bold uppercase">
        {{ $t("sidebar.about", { resource_group: translate(resourceGroup.title) }) }}
    </h2>
    <p class="mb-2 text-sm italic">
        {{ translate(resourceGroup.description) }}
        <a v-if="!isAllowed" :href="helpURI" target="_blank" class="text-tub hover:underline">
            {{ $t("sidebar.help") }}
        </a>
    </p>
    <div v-if="isAuthenticated">
        <UserHappenings :happenings="userHappenings"></UserHappenings>
    </div>
    <div v-else>
        <div class="text-center">
            <p class="block">
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
    </div>
</template>
<script setup>
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
const { loginUser } = useLogin();
const { allowedResourceGroups, isAuthenticated, userHappenings } = storeToRefs(authStore);
const translate = appStore.translate;
const isAllowed = computed(() => {
    return allowedResourceGroups.value.includes(props.resourceGroup.id);
});
const helpURI = computed(() => {
    return props.resourceGroup.help_uri;
});
</script>
