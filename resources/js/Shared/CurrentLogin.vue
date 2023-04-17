<template>
    <div v-if="isAuthenticated">
        Logged in as: {{ currentUser }}
        <button @click="logoutUser" class="p-2 text-white bg-red-500">Logout</button>
    </div>
</template>

<script setup>
import {ref, watch} from "vue";
import {useAuthStore} from "../Stores/AuthStore";

const authStore = useAuthStore();
let isAuthenticated = ref(authStore.isAuthenticated);
let currentUser = ref(authStore.user)

watch(
    () => authStore.isAuthenticated,
    () => {
        console.log('Updated component after auth change: CurrentLogin')
        isAuthenticated.value = authStore.isAuthenticated
        currentUser.value = authStore.user;
    },
)

let logoutUser = () => {
    return authStore.logout()
        .catch(error => console.log(error));
}
</script>

