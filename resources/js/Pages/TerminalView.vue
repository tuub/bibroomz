<template>
    <ResourceGrid id="terminal-view-calendar" :interactive="false"></ResourceGrid>
</template>

<script setup lang="ts">
import ResourceGrid from "@/Components/Calendar/ResourceGrid.vue";
import TerminalLayout from "@/Layouts/TerminalLayout.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { ResourceGroup, Settings } from "@/Stores/AppStore";

defineOptions({ layout: TerminalLayout });

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps<{
    resourceGroup: ResourceGroup;
    settings: Settings;
    hiddenDays: number[];
}>();

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, false);
</script>
