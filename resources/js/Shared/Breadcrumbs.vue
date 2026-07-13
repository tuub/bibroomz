<template>
    <Breadcrumb :home="home" :model="items" />
</template>
<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { usePage } from "@inertiajs/vue3";
import Breadcrumb from "primevue/breadcrumb";
import { computed, inject, ref } from "vue";

const route = inject<ZiggyRouteFn>("ziggyRoute");
const page = usePage<{ route?: string }>();

// Compute the breadcrumbs based on the current route name
const items = computed(() => {
    const breadcrumbs: { label: string; to: string | null }[] = [];

    // Define mappings
    const routesMap: Record<string, { label: string; to: string | null }> = {
        start: { label: "START", to: route("start") },
        privacy_statement: { label: "PRIVACY", to: route("privacy_statement") },
        site_credits: { label: "IMPRINT", to: route("site_credits") },
    };

    const parts = (page.props.route ?? "").split(".").filter(Boolean);
    let accumulatedRoute = "";

    parts.forEach((part, index) => {
        accumulatedRoute += (index ? "." : "") + part;
        if (routesMap[accumulatedRoute]) {
            breadcrumbs.push(routesMap[accumulatedRoute]);
        }
    });

    return breadcrumbs;
});

const home = ref({
    icon: "pi pi-home",
    to: route("start"),
});
</script>
