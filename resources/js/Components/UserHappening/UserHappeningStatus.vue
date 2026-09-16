<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
withDefaults(
    defineProps<{
        label: string;
        icon?: string;
    }>(),
    {
        icon: "default",
    },
);
</script>

<template>
    <Tag :value="label" class="no-wrap px-2 text-xs tracking-wider uppercase"></Tag>
</template>
