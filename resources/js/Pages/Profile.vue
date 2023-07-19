<template>
    <PageHead title="My Profile" />

    <!-- 2xl == 42rems; 42 * 16 (root font-size, e.g. 16) = 672px -->
    <!-- 3xl == 48rems; 48 * 16 (root font-size, e.g. 16) = 768px -->
    <div class="max-w-3xl mx-auto">
        <h1 class="text-xl font-bold">Profile</h1>
        {{ quotas }}
        <p>The current user is {{ user }}.</p>
        <p>The current user reservations are {{ happenings }}.</p>
        <p>The current time is {{ time }}.</p>
        <p><Link href="/my" preserve-scroll>Refresh</Link></p>
    </div>
</template>

<script setup>
import PageHead from "@/Shared/PageHead.vue";
import {useAuthStore} from "@/Stores/AuthStore";
import {storeToRefs} from "pinia";
import {onMounted} from "vue";
import {Modal as FlowbiteModal} from "flowbite";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    time: String,
    user: Object,
    happenings: Object,
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore()

// ------------------------------------------------
// Variables
// ------------------------------------------------
let { quotas } = storeToRefs(authStore)
</script>
