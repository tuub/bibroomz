<template>
    <div class="">
        <span class="font-bold">{{ $t('quota.' + type + '.label') }}</span>:
        <span class="font-normal">{{ $t('quota.' + type + '.value', {current: value.toString(), limit: setting.toString()}) }}</span>
    </div>
</template>

<script setup>
let props = defineProps({
    type: String,
    value: Number,
    setting: Number,
})
</script>
