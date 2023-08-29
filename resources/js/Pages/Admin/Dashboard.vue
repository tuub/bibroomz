<template>
    <PageHead title="Admin" page_type="admin" />

    <div class="dashboard-card-wrapper">
        <DashboardCard
            v-if="hasPermission('view happenings')"
            :title="$t('admin.happenings.index.title')"
            :description="$t('admin.happenings.index.description')"
            :link="route('admin.happening.index')"
            icon="ri-calendar-event-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="canViewInstitutions()"
            :title="$t('admin.institutions.index.title')"
            :description="$t('admin.institutions.index.description')"
            :link="route('admin.institution.index')"
            icon="ri-home-smile-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view resources')"
            :title="$t('admin.resources.index.title')"
            :description="$t('admin.resources.index.description')"
            :link="route('admin.resource.index')"
            icon="ri-map-pin-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view users')"
            :title="$t('admin.users.index.title')"
            :description="$t('admin.users.index.description')"
            :link="route('admin.user.index')"
            icon="ri-user-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view roles')"
            :title="$t('admin.roles.index.title')"
            :description="$t('admin.roles.index.description')"
            :link="route('admin.role.index')"
            icon="ri-group-line"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view statistics')"
            :title="$t('admin.stats.title')"
            :description="$t('admin.stats.description')"
            :link="route('admin.statistic.index')"
            icon="ri-bar-chart-fill"
        ></DashboardCard>
    </div>
</template>

<script setup>
import DashboardCard from "@/Components/Admin/DashboardCard.vue";
import PageHead from "@/Shared/PageHead.vue";
import { useAuthStore } from "@/Stores/AuthStore";

const authStore = useAuthStore();
const { hasPermission, canViewInstitutions } = authStore;
</script>
<style>
.dashboard-card-wrapper {
    margin-top: 10em;
}
.dashboard-card-wrapper > div {
    margin: auto;
}
</style>
