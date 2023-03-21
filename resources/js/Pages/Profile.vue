<template>
    <Head title="My Profile" />
    <!-- 2xl == 42rems; 42 * 16 (root font-size, e.g. 16) = 672px -->
    <!-- 3xl == 48rems; 48 * 16 (root font-size, e.g. 16) = 768px -->
    <div class="max-w-3xl mx-auto">
        <h1 class="text-xl font-bold">Profile</h1>
        <p>The current time is {{ time }}.</p>
        <p><Link href="/my" preserve-scroll>Refresh</Link></p>
    </div>
</template>

<script setup>
defineProps({
    time: String,
})
</script>
