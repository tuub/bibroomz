<template>
    <div>
        <PageHead :title="translate(appStore.resourceGroup.title) + ' - ' + translate(appStore.institution.title)" />

        <XModal />
        <div class="mt-[13.5em] flex flex-col gap-4 xl:flex-row">
            <Calendar @open-modal-component="getModal" />

            <div
                class="w-full space-y-6 border border-gray-200 bg-white p-4 shadow xl:mt-[24px] xl:min-w-[30rem] xl:p-8 dark:border-gray-700 dark:bg-gray-800"
            >
                <div v-if="!isAllowed" class="space-y-4">
                    <div>
                        {{ translate(resourceGroup.description) }}
                    </div>

                    <div v-if="helpURI" class="space-x-1">
                        <span>{{ $t("user_happening.help") }}</span>
                        <a :href="helpURI" target="_blank" class="text-tub hover:underline"> {{ helpURI }}</a>
                    </div>
                </div>

                <UserHappenings v-if="isAuthenticated" :happenings="userHappenings" />
            </div>
        </div>
    </div>
</template>

<script setup>
import Calendar from "@/Components/Calendar.vue";
import UserHappenings from "@/Components/UserHappenings.vue";
import PageHead from "@/Shared/PageHead.vue";
import XModal from "@/Shared/XModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import useModal from "@/Stores/Modal";

import { storeToRefs } from "pinia";
import { computed, onBeforeMount } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: true,
    },
    hiddenDays: {
        type: Array,
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
const modal = useModal();
const { allowedResourceGroups, isAuthenticated, userHappenings } = storeToRefs(authStore);
const translate = appStore.translate;

const isAllowed = computed(() => {
    return allowedResourceGroups.value.includes(props.resourceGroup.id);
});

const helpURI = computed(() => {
    return props.resourceGroup.help_uri;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getModal = (data) => {
    modal.open(data.view, data.content, data.payload, data.actions);
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, props.isMultiTenancy);
});
</script>
