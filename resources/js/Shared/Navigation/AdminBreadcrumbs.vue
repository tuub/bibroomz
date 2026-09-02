<template>
    <Breadcrumb
        v-if="items.length > 0"
        :home="homeItem"
        :model="menuItems"
        class="mb-4 bg-transparent! p-0!"
        data-testid="admin-breadcrumbs"
    >
        <template #item="{ item, label, props: itemProps }">
            <Link v-if="item.url" :href="item.url" v-bind="itemProps.action">
                <span v-if="item.icon" :class="item.icon" v-bind="itemProps.icon"></span>
                <span v-if="label" v-bind="itemProps.label">{{ label }}</span>
            </Link>
            <span v-else class="text-tub font-semibold" aria-current="page">
                <span v-if="item.icon" :class="item.icon"></span>
                <span v-if="label">{{ label }}</span>
            </span>
        </template>
    </Breadcrumb>
</template>

<script setup lang="ts">
import { useAdminBreadcrumbs } from "@/Composables/AdminBreadcrumbs";

import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const { items, home } = useAdminBreadcrumbs();
const menuItems = computed(() => items.value.map((item) => ({ ...item, url: item.url ?? undefined })));
const homeItem = computed(() => ({ ...home.value, url: home.value.url ?? undefined }));
</script>
