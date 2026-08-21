<template>
    <div class="flex flex-wrap">
        <!--
        <div class="px-1">
            <Chip class="my-1 py-0 pl-0 pr-4 text-xs uppercase">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-app-text text-app-page">
                    {{ happeningsCount }}
                </span>
                <span class="ml-0 text-xs font-medium">
                    {{ $t("user_happenings.bookings") }}
                </span>
            </Chip>
        </div>
        -->
        <!-- USER HAPPENING QUOTAS START -->
        <HappeningQuota
            v-for="(value, type) in quotas"
            :key="type"
            class="mb-2 mr-2"
            :type="type"
            :value="value"
            :setting="getQuotaSetting(type)"
        >
        </HappeningQuota>
        <!-- USER HAPPENING QUOTAS END -->
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import HappeningQuota from "./HappeningQuota.vue";

import { computed } from "vue";

const authStore = useAuthStore();
const appStore = useAppStore();

const getQuotaSetting = (type: string) => {
    const setting = appStore.resourceGroup?.settings?.find((entry) => entry.key === `quota_${type}`);
    return Number(setting?.value ?? 0);
};

const quotas = computed<Record<string, number>>(() =>
    Object.fromEntries(
        Object.entries(authStore.quotas).filter(
            (entry): entry is [string, number] => typeof entry[1] === "number" && getQuotaSetting(entry[0]) > 0,
        ),
    ),
);
</script>
