<template>
    <h1 class="text-xl">Info</h1>
    <Label :text="happeningResource" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-map-pin-fill"></Label>
    <Label :text="happeningDate" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-calendar-2-line"></Label>
    <Label :text="happeningStartDisplay" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-time-line"></Label>
    <Label :text="happeningEndDisplay" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-time-fill"></Label>
</template>

<script setup>
import Label from "../Shared/Label.vue";
import dayjs from "dayjs";
import {computed} from "vue";

let props = defineProps({
    happening: Object,
});

const happeningResource = computed(() => {
    return props.happening.resource.title;
})

const happeningDate = computed(() => {
    return dayjs(props.happening.start).format('DD.MM.YYYY');
})

const happeningStartDisplay = computed(() => {
    return dayjs(props.happening.start).format('HH:mm');
})

const happeningEndDisplay = computed(() => {
    return dayjs(props.happening.end).format('HH:mm');
})
</script>
