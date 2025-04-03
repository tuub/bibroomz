<template>
    <Breadcrumb :home="home" :model="items" />
</template>
<script setup>
import { usePage } from "@inertiajs/vue3";
import Breadcrumb from "primevue/breadcrumb";
import { computed, inject, ref } from "vue";

const route = inject("ziggyRoute");
const page = usePage();

console.log(page.props.route);

// Compute the breadcrumbs based on the current route name
const items = computed(() => {
    const breadcrumbs = [];

    // Define mappings
    const routesMap = {
        start: { label: "START", to: route("start") },
        privacy_statement: { label: "PRIVACY", to: route("privacy_statement") },
        site_credits: { label: "IMPRINT", to: route("site_credits") },
    };

    const parts = page.props.route.split(".");
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
