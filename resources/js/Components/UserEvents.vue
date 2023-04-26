<template>
    <h1 class="text-xl font-bold">My Reservations</h1>
    <ul class="list-decimal ml-5">
        <li v-for="event in events" class="mb-2">
            <UserEvent :event="event"></UserEvent>
        </li>
    </ul>
</template>

<script setup>

import UserEvent from './UserEvent.vue'

let props = defineProps({
    events: Object,
})

</script>
