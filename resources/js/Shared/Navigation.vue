<template>
    <nav>
        <ul class="flex space-x-6">
            <li><NavLink href="/" :active="$page.component === 'App'">Home</NavLink></li>
            <li><NavLink href="/my" :active="$page.component === 'Profile'">Profile</NavLink></li>
            <li><NavLink href="/admin/resources" :active="$page.component === 'Admin/Resources/Index'">Admin</NavLink></li>
        </ul>
        <CurrentLogin></CurrentLogin>
    </nav>
</template>

<script>
import NavLink from "./NavLink.vue";
import CurrentLogin from "./CurrentLogin.vue";

export default {
    components: {
        CurrentLogin,
        NavLink,
    },
}
</script>
