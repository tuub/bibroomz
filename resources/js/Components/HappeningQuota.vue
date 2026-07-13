<template>
    <div class="flex rounded text-xs uppercase">
        <div class="flex bg-gray-600 px-2 py-1 font-bold text-white">
            {{ remaining }}
        </div>
        <div class="flex bg-gray-500 px-2 py-1 text-white">
            {{ $tChoice("quota." + type + ".label", remainingValue) }}
        </div>
    </div>

    <!--
        <Chip class="my-1 py-0 pl-0 uppercase">
            <span
                v-if="isPrivileged"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 pb-1 text-lg text-primary-contrast"
            >
                &#8734;
            </span>
            <span
                v-else
                class="flex h-10 w-10 items-center justify-center rounded-full text-xs text-primary-contrast"
                :class="remaining <= 0 ? 'bg-red-500' : 'bg-green-500'"
            >
                {{ remaining }}
            </span>
            <span class="ml-0 text-xs font-medium">
                {{ $tChoice("quota." + type + ".label", remaining) }}
                {{ $t("quota.remaining") }}
            </span>
        </Chip>
            </div>
        -->
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
    const hours = Math.trunc(remainingValue.value);
    const minutes = Math.round((remainingValue.value - hours) * 60);

    if (minutes !== 0) {
        return `${hours}:${String(Math.abs(minutes)).padStart(2, "0")}`;
    }

    return String(hours);
});
</script>
