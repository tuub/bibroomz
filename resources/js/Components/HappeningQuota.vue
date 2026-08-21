<template>
    <div class="flex rounded text-xs uppercase">
        <div class="flex bg-quota-count px-2 py-1 font-bold text-quota-contrast">
            {{ remaining }}
        </div>
        <div class="flex bg-quota-label px-2 py-1 text-quota-contrast">
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
