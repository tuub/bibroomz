<template>
    <button
        type="button"
        class="flex items-center"
        :title="$t(`navigation.theme.${preference}`)"
        :aria-label="$t('accessibility.aria_label.theme_switch', { mode: $t(`navigation.theme.${preference}`) })"
        @click="cyclePreference"
    >
        <i class="pi" :class="icon"></i>
    </button>
</template>

<script setup>
import { useTheme } from "@/Composables/Theme";

import { computed } from "vue";

const { preference, cyclePreference } = useTheme();

const icon = computed(() => {
    return {
        system: "pi-desktop",
        light: "pi-sun",
        dark: "pi-moon",
    }[preference.value];
});
</script>
