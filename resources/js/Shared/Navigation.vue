<template>
    <nav>
        <ul class="flex space-x-6">
            <li><NavLink href="/" :active="$page.component === 'App'">Home</NavLink></li>
            <li v-if="isAuthenticated"><NavLink href="/my" :active="$page.component === 'Profile'">Profile</NavLink></li>
            <li v-if="isAuthenticated"><NavLink href="/admin/resources" :active="$page.component === 'Admin/Resources/Index'">Admin</NavLink></li>
        </ul>
        <CurrentLogin></CurrentLogin>
    </nav>
</template>

<script setup>
import NavLink from "./NavLink.vue";
import CurrentLogin from "./CurrentLogin.vue";
import {ref, watch} from "vue";
import {useAuthStore} from "../Stores/AuthStore";

const authStore = useAuthStore();
let isAuthenticated = ref(authStore.isAuthenticated);

watch(
    () => authStore.isAuthenticated,
    () => {
        console.log('isAuthenticated state changed, do something, captain assblast!')
        isAuthenticated.value = authStore.isAuthenticated
    },
)

</script>
