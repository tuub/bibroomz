<template>
    <div>
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
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { computed } from "vue";

const props = defineProps({
    type: {
        type: String,
        default: "",
    },
    value: {
        type: Number,
        default: 0,
    },
    setting: {
        type: Number,
        default: 0,
    },
});

const authStore = useAuthStore();
const { isPrivileged } = storeToRefs(authStore);

const remaining = computed(() => {
    let remainingTime = props.setting - props.value;
    let hrs = parseInt(Number(remainingTime));
    let min = Math.round((Number(remainingTime) - hrs) * 60);
    if (min !== 0) {
        return hrs + ":" + min;
    }
    return hrs;
});
</script>
