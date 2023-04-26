<template>
    <div class="text-sm">
        <p>{{ happeningDate }}, {{ happeningStart }} - {{ happeningEnd }}</p>
        <p>Raum {{ happening.resource.title }}, {{ happening.resource.location }}</p>
        <button @click="deleteUserHappening(happening)">Delete</button>
    </div>
</template>

<script setup>

import {computed} from "vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import {useAuthStore} from "../Stores/AuthStore";
import {useHappeningStore} from "../Stores/HappeningStore"

dayjs.extend(utc);
const authStore = useAuthStore()
const happeningStore = useHappeningStore()

let props = defineProps({
    happening: Object,
})

const happeningDate = computed(() => {
    return dayjs(props.happening.start).utc().format('DD.MM.YYYY');
})

const happeningStart = computed(() => {
    return dayjs(props.happening.start).utc().format('HH:mm');
})

const happeningEnd = computed(() => {
    return dayjs(props.happening.end).utc().format('HH:mm');
})

const editUserHappening = (happening) => {
    happeningStore.editHappening(happening.id)
}

const deleteUserHappening = (happening) => {
    authStore.deleteUserHappening(happening.id)
}

</script>
