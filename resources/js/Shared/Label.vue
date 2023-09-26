<template>
    <span
        id="label"
        class="text-xs font-semibold inline-block py-2 px-3 rounded uppercase last:mr-0 mr-1 mt-1"
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
