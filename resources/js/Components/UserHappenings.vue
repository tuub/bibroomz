<script setup>
import SidebarBlock from "@/Components/Sidebar/SidebarBlock.vue";

import UserHappening from "./UserHappening.vue";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import ToggleSwitch from "primevue/toggleswitch";
import { computed, ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happenings: {
        type: Object,
        default: () => ({}),
    },
});

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

const isPastHappening = (happening) => {
    return dayjs(happening.end).isBefore(dayjs.utc());
};
</script>

<template>
    <!-- PAST HAPPENINGS TOGGLE START -->
    <div class="inline-flex cursor-pointer items-center text-sm font-medium">
        <ToggleSwitch name="toggle_past_happenings" v-model="hidePast" />
        <label for="toggle_past_happenings" class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
            {{ $t("user_happenings.hide_past_happenings") }}
        </label>
    </div>
    <!-- PAST HAPPENINGS TOGGLE END -->

    <SidebarBlock v-for="(item, index) in happenings" id="user-happenings" :key="index">
        <UserHappening :happening="item" />
    </SidebarBlock>

    <!-- USER HAPPENINGS START -->
    <!--
    <div id="user-happenings">
        <UserHappening
            v-for="(item, index) in happenings"
            :key="index"
            :happening="item">
        </UserHappening>
    </div>
    -->
</template>
