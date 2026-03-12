<script setup>
import DashboardCard from "@/Components/Admin/DashboardCard.vue";
import { useAuthStore } from "@/Stores/AuthStore";

const authStore = useAuthStore();
const { hasPermission, canViewInstitutions } = authStore;
</script>

<template>
    <div class="flex flex-row flex-wrap items-stretch justify-around">
        <DashboardCard
            v-if="hasPermission('view_happenings')"
            :title="$t('admin.happenings.index.title')"
            :description="$t('admin.happenings.index.description')"
            :link="route('admin.happening.index')"
            icon="ri-calendar-event-fill"
        />
        <DashboardCard
            v-if="canViewInstitutions()"
            :title="$t('admin.institutions.index.title')"
            :description="$t('admin.institutions.index.description')"
            :link="route('admin.institution.index')"
            icon="ri-home-smile-fill"
        />
        <DashboardCard
            v-if="hasPermission('view_users')"
            :title="$t('admin.users.index.title')"
            :description="$t('admin.users.index.description')"
            :link="route('admin.user.index')"
            icon="ri-user-fill"
        />
        <DashboardCard
            v-if="hasPermission('view_roles')"
            :title="$t('admin.roles.index.title')"
            :description="$t('admin.roles.index.description')"
            :link="route('admin.role.index')"
            icon="ri-group-line"
        />
        <DashboardCard
            v-if="hasPermission('view_user_groups')"
            :title="$t('admin.user_groups.index.title')"
            :description="$t('admin.user_groups.index.description')"
            :link="route('admin.user_group.index')"
            icon="ri-shield-keyhole-fill"
        />
    </div>
</template>
