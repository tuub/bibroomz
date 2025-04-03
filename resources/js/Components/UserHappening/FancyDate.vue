<template>
    <div
        class="h-16 w-16 shrink-0 grow-0 items-center justify-center rounded-full pt-2.5 text-center uppercase text-white"
        :class="cssClass"
    >
        <div class="block text-xs">{{ formattedFancyDate.month }}</div>
        <div class="block text-xl font-bold">{{ formattedFancyDate.day }}</div>
    </div>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";

import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        required: true,
    },
    cssClass: {
        type: String,
        required: true,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Computed
// ------------------------------------------------
const formattedFancyDate = computed(() => {
    return appStore.formatFancyDate(props.happening.start, true);
});
</script>

<style lang="postcss" scoped>
.booked {
    @apply bg-green-500;
}

.reserved {
    @apply bg-yellow-500;
}

.over {
    @apply bg-gray-500;
}
</style>
