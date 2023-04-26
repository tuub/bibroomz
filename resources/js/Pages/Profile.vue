<template>
    <Head title="My Profile" />

    <!-- 2xl == 42rems; 42 * 16 (root font-size, e.g. 16) = 672px -->
    <!-- 3xl == 48rems; 48 * 16 (root font-size, e.g. 16) = 768px -->
    <div class="max-w-3xl mx-auto">
        <h1 class="text-xl font-bold">Profile</h1>
        <p>The current user is {{ user }}.</p>
        <p>The current user reservations are {{ user_happenings }}.</p>
        <p>The current time is {{ time }}.</p>
        <p><Link href="/my" preserve-scroll>Refresh</Link></p>
    </div>
</template>

<script setup>
import HappeningInfo from '../Components/HappeningInfo.vue';
import UserInfo from '../Components/UserInfo.vue';
import { ref } from 'vue'

let happeningData = {
    resource: '1',
    start: '15:30:00',
    end: '18:00:00',
}
let userData = {
    id: '99',
    name: 'dummy',
}

defineProps({
    time: String,
    user: Object,
    happenings: Object,
})
</script>
