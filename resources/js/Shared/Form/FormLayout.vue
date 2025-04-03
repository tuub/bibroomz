<template>
    <div>
        <form class="max-w mx-auto mt-8 space-y-6">
            <slot />
        </form>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>
