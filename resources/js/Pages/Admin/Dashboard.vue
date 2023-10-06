<template>
    <PageHead title="Admin" page-type="admin" />

    <div class="dashboard-card-wrapper">
        <DashboardCard
            v-if="hasPermission('view_happenings')"
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
            v-if="hasPermission('view_resource_groups')"
            :title="$t('admin.resource_groups.index.title')"
            :description="$t('admin.resource_groups.index.description')"
            :link="route('admin.resource_group.index')"
            icon="ri-map-pin-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view_users')"
            :title="$t('admin.users.index.title')"
            :description="$t('admin.users.index.description')"
            :link="route('admin.user.index')"
            icon="ri-user-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view_roles')"
            :title="$t('admin.roles.index.title')"
            :description="$t('admin.roles.index.description')"
            :link="route('admin.role.index')"
            icon="ri-group-line"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view_permissions')"
            :title="$t('admin.permissions.index.title')"
            :description="$t('admin.permissions.index.description')"
            :link="route('admin.permission.index')"
            icon="ri-door-lock-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view_permission_groups')"
            :title="$t('admin.permission_groups.index.title')"
            :description="$t('admin.permission_groups.index.description')"
            :link="route('admin.permission_group.index')"
            icon="ri-shield-keyhole-fill"
        ></DashboardCard>
        <DashboardCard
            v-if="hasPermission('view_statistics')"
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
.dashboard-card-wrapper > div {
    margin: 0px 10px 20px 10px;
    float: left;
    height: 150px;
    width: 250px;
    padding: 40px 20px 20px 20px;
    text-align: center;
}
</style>
