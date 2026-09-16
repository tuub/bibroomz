<template>
    <Breadcrumb :home="home" :model="items">
        <template #item="{ item, label, props: itemProps }">
            <Link :href="item.url" v-bind="itemProps.action">
                <span v-if="item.icon" class="p-breadcrumb-item-icon" :class="item.icon"></span>
                <span v-if="label" class="p-breadcrumb-item-label">{{ label }}</span>
            </Link>
        </template>
    </Breadcrumb>
</template>
<script setup lang="ts">
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { Link, usePage } from "@inertiajs/vue3";
import Breadcrumb from "primevue/breadcrumb";
import { computed, inject, ref } from "vue";

const route = inject<ZiggyRouteFn>("ziggyRoute")!;
const page = usePage<{ route?: string }>();

// Compute the breadcrumbs based on the current route name
const items = computed(() => {
    const breadcrumbs: { label: string; url: string }[] = [];

    // Define mappings
    const routesMap: Record<string, { label: string; url: string }> = {
        start: { label: "START", url: route("start") },
        privacy_statement: { label: "PRIVACY", url: route("privacy_statement") },
        site_credits: { label: "IMPRINT", url: route("site_credits") },
    };

    const parts = (page.props.route ?? "").split(".").filter(Boolean);
    let accumulatedRoute = "";

    parts.forEach((part, index) => {
        accumulatedRoute += (index ? "." : "") + part;
        const routeMapItem = routesMap[accumulatedRoute];
        if (routeMapItem) {
            breadcrumbs.push(routeMapItem);
        }
    });

    return breadcrumbs;
});

const home = ref({
    icon: "pi pi-home",
    url: route("start"),
});
</script>
