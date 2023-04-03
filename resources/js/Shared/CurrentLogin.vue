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
        console.log('isAuthenticated state changed, do something, captain assblast!')
        isAuthenticated.value = authStore.isAuthenticated
        currentUser.value = authStore.user ?? '';
    },
)

let logoutUser = () => {
    console.log('LOGOUT')

    return authStore.logout()
        .catch(error => console.log(error));
}

// console.log(authStore)

// https://github.com/inertiajs/inertia/discussions/505?sort=top#discussioncomment-381019

//console.log('currentUser')
//console.log(currentUser.value)
// FIXME: computed prop "username"
</script>

