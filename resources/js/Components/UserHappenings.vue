<script setup lang="ts">
import SidebarBlock from "@/Components/Sidebar/SidebarBlock.vue";
import type { Happening } from "@/Stores/HappeningStore";

import UserHappening from "./UserHappening.vue";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import ToggleSwitch from "primevue/toggleswitch";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        happenings?: Happening[];
    }>(),
    {
        happenings: () => [],
    },
);

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const hidePast = ref(true);

const happenings = computed(() => {
    if (hidePast.value) {
        return props.happenings.filter((happening) => !isPastHappening(happening));
    }

    return props.happenings;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------

const isPastHappening = (happening: Happening) => {
    return dayjs(happening.end).isBefore(dayjs.utc());
};
</script>

<template>
    <!-- PAST HAPPENINGS TOGGLE START -->
    <div class="inline-flex cursor-pointer items-center text-sm font-medium">
        <ToggleSwitch v-model="hidePast" input-id="toggle-past-happenings" name="toggle_past_happenings" />
        <label
            for="toggle-past-happenings"
            data-testid="toggle-past-happenings-label"
            class="ml-3 text-sm font-medium text-app-text dark:text-app-muted"
        >
            {{ $t("user_happenings.hide_past_happenings") }}
        </label>
    </div>
    <!-- PAST HAPPENINGS TOGGLE END -->

    <SidebarBlock v-for="item in happenings" :key="item.id" :data-testid="`user-happening-${item.id}`">
        <UserHappening :happening="item" />
    </SidebarBlock>
</template>
