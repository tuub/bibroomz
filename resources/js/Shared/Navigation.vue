<template>
    <nav>
        <ul class="flex space-x-6">
            <li>
                <NavLink href="/" :active="$page.component === 'App'">Home</NavLink>
            </li>
            <li v-if="isAuthenticated">
                <NavLink href="/my" :active="$page.component === 'Profile'">Profile</NavLink>
            </li>
            <li v-if="isAuthenticated">
                <NavLink href="/admin/dashboard" :active="$page.component === 'Admin/Dashboard'">Admin</NavLink>
            </li>
        </ul>
        <CurrentLogin></CurrentLogin>
    </nav>
</template>

<script setup>
import NavLink from "./NavLink.vue";
import CurrentLogin from "./CurrentLogin.vue";
import {useAuthStore} from "../Stores/AuthStore";
import {storeToRefs} from "pinia";

const authStore = useAuthStore();
let { isAuthenticated } = storeToRefs(authStore);
</script>
