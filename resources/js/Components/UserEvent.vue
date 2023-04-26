<template>
    <div class="text-sm">
        <p>{{ eventDate }}, {{ eventStart }} - {{ eventEnd }}</p>
        <p>Raum {{ event.resource.title }}, {{ event.resource.location }}</p>
    </div>
</template>

<script setup>

import {computed} from "vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);

let props = defineProps({
    event: Object,
})

const eventDate = computed(() => {
    return dayjs(props.event.start).utc().format('DD.MM.YYYY');
})

const eventStart = computed(() => {
    return dayjs(props.event.start).utc().format('HH:mm');
})

const eventEnd = computed(() => {
    return dayjs(props.event.end).utc().format('HH:mm');
})

</script>
