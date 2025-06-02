<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        default: () => ({}),
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
const translate = appStore.translate;
const { isPrivileged } = storeToRefs(authStore);

// ------------------------------------------------
// Computed
// ------------------------------------------------
const happeningStart = computed(() => {
    return appStore.formatTime(props.happening.start, true);
});

const happeningEnd = computed(() => {
    return appStore.formatTime(props.happening.end, true);
});

const isLabelPresent = computed(() => {
    return props.happening.label && !Array.isArray(props.happening.label);
});
</script>
<template>
    <ul class="w-full space-y-1 text-sm">
        <li class="flex font-bold">
            <i class="ri-time-fill mr-1"></i>
            <div>{{ happeningStart }} - {{ happeningEnd }}</div>
        </li>
        <li v-if="isLabelPresent" class="flex">
            <i class="ri-price-tag-3-fill mr-1"></i>
            <div>"{{ translate(happening.label) }}"</div>
        </li>
        <li class="flex">
            <i class="ri-map-pin-fill mr-1"></i>
            <div>
                {{ happening.resource.resourceGroup }}
                {{ happening.resource.title }}
            </div>
        </li>
        <li class="flex">
            <template v-if="isPrivileged">
                <i class="ri-admin-fill mr-1"></i>
            </template>
            <template v-else>
                <template v-if="happening.isVerificationRequired">
                    <i class="ri-group-fill mr-1"></i>
                </template>
                <template v-else>
                    <i class="ri-user-fill mr-1"></i>
                </template>
            </template>
            <div class="flex">
                {{ happening.user_01 }}
                <template v-if="happening.isVerificationRequired && happening.user_02">
                    & {{ happening.user_02 }}
                </template>
            </div>
        </li>
    </ul>
</template>
