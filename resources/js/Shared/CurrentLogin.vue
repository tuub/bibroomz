<template>
    <div v-if="isAuthenticated">
        Logged in as: {{ currentUser }}
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
import{ computed } from "vue";
import { usePage } from "@inertiajs/vue3";

let currentUser = ''

// https://github.com/inertiajs/inertia/discussions/505?sort=top#discussioncomment-381019
const isAuthenticated = computed(() => usePage().props.auth !== null)
if (isAuthenticated) {
    currentUser = computed(() => usePage().props.auth.user.name)
}

//console.log('currentUser')
//console.log(currentUser.value)
// FIXME: computed prop "username"
</script>

