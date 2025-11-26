<template>
    <ul v-for="(component, index) in $slots.default?.()" :key="index">
        <li class="text-nowrap"><component :is="component" /></li>
    </ul>
</template>
