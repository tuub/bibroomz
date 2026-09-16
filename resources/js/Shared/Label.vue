<template>
    <span
        id="label"
        class="mt-1 mr-1 inline-block rounded-sm px-3 py-2 text-xs font-semibold uppercase last:mr-0"
        :class="[textColorClass, bgColorClass]"
        :style="{ color: textColor, 'background-color': bgColor }"
    >
        <span v-if="iconClass" :class="iconClass" class="mr-1"></span>
        {{ text }}
    </span>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
withDefaults(
    defineProps<{
        text: string;
        bgColor?: string;
        bgColorClass?: string;
        textColor?: string;
        textColorClass?: string;
        iconClass: string;
    }>(),
    {
        bgColor: "",
        bgColorClass: "",
        textColor: "",
        textColorClass: "",
    },
);
</script>
