<template>
    <span id="label"
          class="text-xs font-semibold inline-block py-2 px-3 uppercase rounded uppercase last:mr-0 mr-1 mt-1"
          :class="[classTextColor, classBgColor]"
          :style="{'color': styleTextColor, 'background-color': styleBgColor}">
        <span :class="iconClass" class="mr-1" v-if="iconClass"></span>
        {{ text }}
    </span>
</template>

<script setup>

import {computed} from "vue";

const props = defineProps({
    text: String,
    bgColor: String,
    bgColorClass: String,
    textColor: String,
    textColorClass: String,
    iconClass: String,
})

const classTextColor = computed(() => {
    return props.textColorClass !== 'undefined' ? props.textColorClass : ''
})

const classBgColor = computed(() => {
    return props.bgColorClass !== 'undefined' ? props.bgColorClass : ''
})

const styleTextColor = computed(() => {
    return props.textColor !== undefined ? props.textColor : ''
})

const styleBgColor = computed(() => {
    return props.bgColor !== undefined ? props.bgColor : ''
})
</script>
