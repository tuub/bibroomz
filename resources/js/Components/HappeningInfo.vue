<template>
    <Label
        :text="happeningResource"
        bg-color-class="bg-gray-200"
        text-color-class="text-gray-700"
        icon-class="ri-map-pin-fill"
    ></Label>
    <Label
        :text="happeningDate"
        bg-color-class="bg-gray-200"
        text-color-class="text-gray-700"
        icon-class="ri-calendar-2-line"
    ></Label>
    <Label
        :text="happeningStartDisplay"
        bg-color-class="bg-gray-200"
        text-color-class="text-gray-700"
        icon-class="ri-time-line"
    ></Label>
    <Label
        :text="happeningEndDisplay"
        bg-color-class="bg-gray-200"
        text-color-class="text-gray-700"
        icon-class="ri-time-fill"
    ></Label>
    <Label
        :text="happeningLength"
        bg-color-class="bg-gray-200"
        text-color-class="text-gray-700"
        icon-class="ri-hourglass-2-fill"
    ></Label>
    <ResourceInfo
        class="mt-4 text-sm"
        :resource="happening.resource"
        :is-expandable="true"
        :is-initially-visible="false"
    />
</template>

<script setup>
import ResourceInfo from "@/Components/ResourceInfo.vue";
import Label from "@/Shared/Label.vue";

import dayjs from "dayjs";
import duration from "dayjs/plugin/duration";
import utc from "dayjs/plugin/utc";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        required: true,
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);
dayjs.extend(duration);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happeningResource = computed(() => {
    return props.happening.resource.resourceGroup + " " + props.happening.resource.title.toString();
});

const happeningDate = computed(() => {
    return dayjs.utc(props.happening.start).format("DD.MM.YYYY");
});

const happeningStartDisplay = computed(() => {
    return dayjs.utc(props.happening.start).format("HH:mm");
});

const happeningEndDisplay = computed(() => {
    return dayjs.utc(props.happening.end).format("HH:mm");
});

const happeningLength = computed(() => {
    const length = dayjs.duration(dayjs(props.happening.end).diff(dayjs(props.happening.start)));

    const lengthValues =
        length.asMinutes() > 90
            ? { number: length.asHours().toString().replace(/[.]/, ","), unit: "H" }
            : { number: length.asMinutes(), unit: "MIN" };

    return lengthValues["number"] + " " + lengthValues["unit"];
});
</script>
