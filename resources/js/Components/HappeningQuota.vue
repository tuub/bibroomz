<template>
    <div class="flex rounded-sm text-xs uppercase">
        <div class="bg-quota-count text-quota-contrast flex px-2 py-1 font-bold">
            {{ remaining }}
        </div>
        <div class="bg-quota-label text-quota-contrast flex px-2 py-1">
            {{ $tChoice("quota." + type + ".label", remainingValue) }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    type: string;
    value: number;
    setting: number;
}>();

const remainingValue = computed(() => props.setting - props.value);

const remaining = computed(() => {
    // Math.trunc(-0.5) is -0, which stringifies to "0" and silently drops the
    // overage sign for sub-hour negative values. Compute the sign separately
    // from the (always non-negative) magnitude so it is never lost.
    const sign = remainingValue.value < 0 ? "-" : "";
    const magnitude = Math.abs(remainingValue.value);
    const hours = Math.trunc(magnitude);
    const minutes = Math.round((magnitude - hours) * 60);

    if (minutes !== 0) {
        return `${sign}${hours}:${String(minutes).padStart(2, "0")}`;
    }

    return `${sign}${hours}`;
});
</script>
