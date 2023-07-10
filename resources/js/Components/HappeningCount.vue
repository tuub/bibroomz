<template>
    <div class="inline-flex items-center justify-center w-8 h-8 ml-2 font-bold text-blue-800 bg-blue-200 rounded-full">
        {{ count }}
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    count: Number,
});
</script>
