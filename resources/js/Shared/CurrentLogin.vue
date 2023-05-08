<template>
    <div v-if="isAuthenticated">
        Logged in as: {{ currentUser }}
        <button @click="logoutUser" class="p-2 text-white bg-red-500">Logout</button>
    </div>
</template>

<script setup>
import {useAuthStore} from "../Stores/AuthStore";
import {storeToRefs} from "pinia";

const authStore = useAuthStore();
let { isAuthenticated, user: currentUser } = storeToRefs(authStore);

let logoutUser = async () => {
    try {
        return await authStore.logout();
    } catch (error) {
        return console.log(error);
    }
};
</script>

