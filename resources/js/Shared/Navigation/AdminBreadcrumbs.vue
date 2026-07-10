<template>
    <Breadcrumb v-if="items.length > 0" :home="home" :model="items" class="mb-4 !bg-transparent !p-0">
        <template #item="{ item, label, props: itemProps }">
            <Link v-if="item.url" :href="item.url" v-bind="itemProps.action">
                <span v-if="item.icon" :class="item.icon" v-bind="itemProps.icon"></span>
                <span v-if="label" v-bind="itemProps.label">{{ label }}</span>
            </Link>
            <span v-else class="font-semibold text-tub" aria-current="page">
                <span v-if="item.icon" :class="item.icon"></span>
                <span v-if="label">{{ label }}</span>
            </span>
        </template>
    </Breadcrumb>
</template>

<script setup>
import { useAdminBreadcrumbs } from "@/Composables/AdminBreadcrumbs";

import { Link } from "@inertiajs/vue3";

const { items, home } = useAdminBreadcrumbs();
</script>
