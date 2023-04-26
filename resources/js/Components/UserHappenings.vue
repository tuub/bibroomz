<template>
    <h1 class="text-xl font-bold">My Reservations</h1>
    <ul class="list-decimal ml-5">
        <li v-for="happening in happenings" class="mb-2">
            <UserHappening :happening="happening"></UserHappening>
        </li>
    </ul>
</template>

<script setup>

import UserHappening from './UserHappening.vue'

let props = defineProps({
    happenings: Object,
})

</script>
