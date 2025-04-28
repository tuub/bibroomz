<template>
    <button class="space-x-1 px-1" :class="type" :title="label">
        <i v-if="icon" class="text-tiny" :class="icon"></i>
        <span class="text-tiny">{{ label }}</span>
    </button>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: false,
        default: "default",
    },
    type: {
        type: String,
        required: true,
    },
});
</script>

<style lang="postcss" scoped>
.default {
    @apply bg-white text-black;
}

.delete {
    @apply bg-red-500 text-white;
}

.edit {
    @apply bg-gray-500 text-white;
}

.verify {
    @apply bg-green-500 text-white;
}
</style>
