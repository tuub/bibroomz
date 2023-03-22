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
import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";

const isAuthenticated = computed(() => usePage().props.auth !== null);
let isAdmin = false;
if (isAuthenticated) {
    isAdmin = computed(() => usePage().props.auth.user !== null);
}
</script>
