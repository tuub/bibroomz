<template>
    <div>
        Logged in as: {{ $page.props.auth.user.name }}
        <Link
            href="/logout"
            method="post"
            as="button"
            class="p-2 text-white bg-red-500">
            Logout
        </Link>
    </div>
</template>

<script setup>
// FIXME: computed prop "username"
let isAuthenticated = !!$page.props.auth.user.name;
</script>

