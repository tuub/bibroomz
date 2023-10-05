<template>
    <div class="inline-flex items-center justify-center w-8 h-8 ml-2 font-bold text-red-800 bg-red-200 rounded-full">
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
