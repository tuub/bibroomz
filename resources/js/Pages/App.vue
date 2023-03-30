<template>
    <Head title="Home" />
    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
            <div v-if="isAuthenticated">
                <UserReservations></UserReservations>
            </div>
            <div v-else>
                <LoginForm></LoginForm>
            </div>
        </div>
        <div id="calendar" class="basis-4/5 md:basis-4/5">
            <Calendar></Calendar>
        </div>
    </div>
</template>

<script setup>
import Calendar from "../Components/Calendar.vue";
import LoginForm from "../Components/LoginForm.vue";
import UserReservations from "../Components/UserReservations.vue"
import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";
import {useUserStore} from  "../Stores/UserStore.js"

const isAuthenticated = computed(() => usePage().props?.auth !== null).value
if (isAuthenticated) {
    const userStore = useUserStore();
    userStore.addUser(usePage().props.auth.user)
}


</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
