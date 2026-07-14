<script setup lang="ts">
import MainLayout from "@/Layouts/MainLayout.vue";
import AdminBreadcrumbs from "@/Shared/Navigation/AdminBreadcrumbs.vue";
</script>
<template>
    <MainLayout>
        <template #breadcrumbs>
            <AdminBreadcrumbs />
        </template>
        <slot />
    </MainLayout>
</template>
