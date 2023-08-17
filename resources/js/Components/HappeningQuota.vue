<template>
    <div>
        <span class="font-bold"> {{ $t("quota." + type + ".label") }} </span>:
        <span class="font-normal">{{
            $t("quota." + type + ".value", { current: value.toString(), limit: setting.toString() })
        }}</span>
    </div>
</template>

<script setup>
defineProps({
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
</script>
