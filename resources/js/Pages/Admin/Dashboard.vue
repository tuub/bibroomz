<template>
    <PageHead title="Admin" page_type="admin" />

    <div class="flex items-center justify-center">
        <DashboardCard title="Happenings" icon="ri-calendar-event-fill" content="Here you can edit all reservations." :link="route('admin.happening.index')"></DashboardCard>
        <DashboardCard title="Institutions" icon="ri-home-smile-fill" content="Here you can edit all institutions." :link="route('admin.institution.index')"></DashboardCard>
        <DashboardCard title="Resources" icon="ri-map-pin-fill" content="Here you can edit all rooms." :link="route('admin.resource.index')"></DashboardCard>
        <DashboardCard title="Users" icon="ri-user-fill" content="Here you can see and ban users." :link="route('admin.user.index')"></DashboardCard>
        <DashboardCard title="Stats" icon="ri-bar-chart-fill" content="Some statistics regarding the reservations." :link="route('admin.statistic.index')"></DashboardCard>
    </div>
</template>

<script setup>
import DashboardCard from "@/Components/Admin/DashboardCard.vue";
import PageHead from "@/Shared/PageHead.vue";
</script>
