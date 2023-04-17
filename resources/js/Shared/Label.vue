<template>
    <span id="label"
          class="text-xs font-semibold inline-block py-2 px-3 uppercase rounded uppercase last:mr-0 mr-1"
          :class="[classTextColor, classBgColor]"
          :style="{'color': styleTextColor, 'background-color': styleBgColor}">
        <span :class="iconClass" class="mr-1"></span>
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
    console.log('classTextColor = ' + props.textColorClass !== 'undefined' ? 'text-' + props.textColorClass : '')
    return props.textColorClass !== 'undefined' ? 'text-' + props.textColorClass : ''
})

const classBgColor = computed(() => {
    console.log('classBgColor = ' + props.bgColorClass !== 'undefined' ? 'bg-' + props.bgColorClass : '')
    return props.bgColorClass !== 'undefined' ? 'bg-' + props.bgColorClass : ''
})

const styleTextColor = computed(() => {
    return props.textColor !== undefined ? props.textColor : ''
})

const styleBgColor = computed(() => {
    return props.bgColor !== undefined ? props.bgColor : ''
})

//console.log('*******************')
//console.log('props.textColor: ' + props.textColor)
//console.log('props.textColorClass: ' + props.textColorClass)
//console.log('props.bgColor: ' + props.bgColor)
//console.log('props.bgColorClass: ' + props.bgColorClass)



</script>
