<template>
    <div class="flex flex-col">
        <div class="flex flex-col" :class="isPast ? 'opacity-50' : 'opacity-100'">
            <!-- STATUS START -->
            <div class="mb-2 flex space-x-1">
                <div class="flex space-x-1">
                    <div class="flex">
                        <template v-if="isPast">
                            <UserHappeningStatus :label="$t('user_happenings.item.past_happening')" />
                        </template>
                        <template v-else-if="isPresent">
                            <UserHappeningStatus :label="$t('user_happenings.item.present_happening')" />
                        </template>
                        <template v-else>
                            <UserHappeningStatus :label="$t('user_happenings.item.future_happening')" />
                        </template>
                    </div>
                </div>
                <div class="flex">
                    <template v-if="!isPast && happening.isVerified">
                        <SidebarLabel :label="$t('user_happenings.item.verified')" icon="true" severity="success" />
                    </template>
                    <template v-if="!isPast && !happening.isVerified">
                        <SidebarLabel :label="$t('user_happenings.item.unverified')" icon="true" severity="warn" />
                    </template>
                </div>
            </div>
            <!-- STATUS END -->

            <!-- HAPPENING DATA START -->
            <div class="flex w-full">
                <div class="flex w-9/12 space-y-1">
                    <UserHappeningData :happening="happening" />
                </div>
                <div class="flex w-3/12 items-center justify-center">
                    <FancyDate :happening="happening" :css-class="getStatusClass"></FancyDate>
                </div>
            </div>

            <div class="mt-2 flex w-full space-x-1">
                <UserHappeningActions :happening="happening" />
            </div>
            <!-- HAPPENING DATA END -->
        </div>
    </div>
</template>

<script setup lang="ts">
import SidebarLabel from "@/Components/Sidebar/SidebarLabel.vue";
import FancyDate from "@/Components/UserHappening/FancyDate.vue";
import UserHappeningActions from "@/Components/UserHappening/UserHappeningActions.vue";
import UserHappeningData from "@/Components/UserHappening/UserHappeningData.vue";
import UserHappeningStatus from "@/Components/UserHappening/UserHappeningStatus.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { Happening } from "@/Stores/HappeningStore";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps<{
    happening: Happening;
}>();

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;

// ------------------------------------------------
// Computed
// ------------------------------------------------
const happening = computed<Happening>(() => {
    const resource = props.happening.resource ?? {};

    return {
        ...props.happening,
        resource: {
            ...resource,
            title: translate(resource.title),
            location: translate(resource.location),
            description: translate(resource.description),
            resourceGroup: translate(resource.resourceGroup),
            institution: resource.institution,
        },
    };
});

const isPast = computed(() => {
    return dayjs(props.happening.end).isBefore(dayjs.utc());
});

const isPresent = computed(() => {
    return dayjs(props.happening.start).isBefore(dayjs.utc()) && dayjs(props.happening.end).isAfter(dayjs.utc());
});

const getStatusClass = computed(() => {
    if (isPast.value) {
        return "over";
    } else if (props.happening.isVerified) {
        return "status-booking";
    } else {
        return "status-reservation";
    }
});
</script>
