<template>
    <Head title="Home" />
    {{ isAuthenticated }}
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

import { useAuthStore } from '../Stores/AuthStore';
import {onMounted, ref, watch} from "vue";

const authStore = useAuthStore();
let currentUser = ref('')
let isAuthenticated = ref(authStore.isAuthenticated);

watch(
    () => authStore.isAuthenticated,
    () => {
        console.log('isAuthenticated state changed, do something, captain assblast!')
        isAuthenticated.value = authStore.isAuthenticated
        currentUser.value = authStore.user ?? '';
    },
)

onMounted(() => {
    // check auth session in backend
    authStore.check()
});


</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
