<template>
    <PageHead title="Admin" page_type="admin" />

    <div class="flex items-center justify-center">
        <DashboardCard :title="$t('admin.happenings.index.title')"
                       :description="$t('admin.happenings.index.description')"
                       :link="route('admin.happening.index')"
                       icon="ri-calendar-event-fill"></DashboardCard>
        <DashboardCard :title="$t('admin.institutions.index.title')"
                       :description="$t('admin.institutions.index.description')"
                       :link="route('admin.institution.index')"
                       icon="ri-home-smile-fill"></DashboardCard>
        <DashboardCard :title="$t('admin.resources.index.title')"
                       :description="$t('admin.resources.index.description')"
                       :link="route('admin.resource.index')"
                       icon="ri-map-pin-fill"></DashboardCard>
        <DashboardCard :title="$t('admin.users.index.title')"
                       :description="$t('admin.users.index.description')"
                       :link="route('admin.user.index')"
                       icon="ri-user-fill"></DashboardCard>
        <DashboardCard :title="$t('admin.stats.title')"
                       :description="$t('admin.stats.description')"
                       :link="route('admin.statistic.index')"
                       icon="ri-bar-chart-fill"></DashboardCard>
    </div>
</template>

<script setup>
import DashboardCard from "@/Components/Admin/DashboardCard.vue";
import PageHead from "@/Shared/PageHead.vue";
</script>
