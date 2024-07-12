<template>
    <span
        id="label"
        class="mr-1 mt-1 inline-block rounded px-3 py-2 text-xs font-semibold uppercase last:mr-0"
        :class="[textColorClass, bgColorClass]"
        :style="{ color: textColor, 'background-color': bgColor }"
    >
        <span v-if="iconClass" :class="iconClass" class="mr-1"></span>
        {{ text }}
    </span>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    text: {
        type: String,
        required: true,
    },
    bgColor: {
        type: String,
        default: "",
    },
    bgColorClass: {
        type: String,
        default: "",
    },
    textColor: {
        type: String,
        default: "",
    },
    textColorClass: {
        type: String,
        default: "",
    },
    iconClass: {
        type: String,
        required: true,
    },
});
</script>
