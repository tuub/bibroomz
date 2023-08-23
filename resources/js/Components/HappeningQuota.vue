<template>
    <div>
        <span class="font-bold"> {{ $t("quota." + type + ".label") }} </span>:
        <span class="font-normal">{{
            $t("quota." + type + ".value", {
                remaining,
            })
        }}</span>
    </div>
</template>

<script setup>
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

const remaining = computed(() => {
    return props.setting - props.value;
});
</script>
