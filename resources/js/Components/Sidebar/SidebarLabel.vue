<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
withDefaults(
    defineProps<{
        label: string;
        icon?: string;
        severity?: string;
    }>(),
    {
        icon: "",
        severity: "info",
    },
);
</script>
<template>
    <Tag :severity="severity" :value="label" class="p-1 text-xs uppercase"></Tag>
</template>
