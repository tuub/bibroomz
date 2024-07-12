<template>
    <div class="ml-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-200 font-bold text-red-800">
        {{ count }}
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    count: {
        type: Number,
        required: true,
    },
});
</script>
