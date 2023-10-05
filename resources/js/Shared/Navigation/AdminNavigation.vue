<template>
    <NavigationMenu>
        <ul>
            <li>
                <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')" :is-active="isPageDashboard">
                    {{ $t("navigation.admin.dashboard") }}
                </NavLink>
            </li>
            <li>
                <NavLink
                    icon="ri-calendar-event-fill"
                    :href="route('admin.happening.index')"
                    :is-active="isPageHappenings"
                >
                    {{ $t("navigation.admin.happenings") }}
                </NavLink>
            </li>
            <li v-if="canViewInstitutions()">
                <NavLink
                    icon="ri-home-smile-fill"
                    :href="route('admin.institution.index')"
                    :is-active="isPageInstitutions"
                >
                    {{ $t("navigation.admin.institutions") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_resource_groups')">
                <NavLink
                    icon="ri-map-pin-fill"
                    :href="route('admin.resource_group.index')"
                    :is-active="isPageResourceGroups"
                >
                    {{ $t("navigation.admin.resource_groups") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_users')">
                <NavLink icon="ri-user-fill" :href="route('admin.user.index')" :is-active="isPageUsers">
                    {{ $t("navigation.admin.users") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_roles')">
                <NavLink icon="ri-group-line" :href="route('admin.role.index')" :is-active="isPageRoles">
                    {{ $t("navigation.admin.roles") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_permissions')">
                <NavLink
                    icon="ri-shield-keyhole-fill"
                    :href="route('admin.permission.index')"
                    :is-active="isPagePermissions"
                >
                    {{ $t("navigation.admin.permissions") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_permission_groups')">
                <NavLink
                    icon="ri-shield-keyhole-fill"
                    :href="route('admin.permission_group.index')"
                    :is-active="isPagePermissionGroups"
                >
                    {{ $t("navigation.admin.permission_groups") }}
                </NavLink>
            </li>
            <li v-if="hasPermission('view_statistics')">
                <NavLink icon="ri-bar-chart-fill" :href="route('admin.statistic.index')" :is-active="isPageStats">
                    {{ $t("navigation.admin.stats") }}
                </NavLink>
            </li>
            <li>
                <NavLink icon="ri-shut-down-line" :href="getExitUri">
                    {{ $t("navigation.admin.exit") }}
                </NavLink>
            </li>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const inertiaPage = usePage();
const institution = appStore.institution;
const resourceGroup = appStore.resourceGroup;

const { hasPermission, canViewInstitutions } = authStore;

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPageDashboard = computed(() => {
    return inertiaPage.component.startsWith("Admin/Dashboard");
});

const isPageHappenings = computed(() => {
    return inertiaPage.component.startsWith("Admin/Happenings");
});

const isPageInstitutions = computed(() => {
    const isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "institution";
    return (
        inertiaPage.component.startsWith("Admin/Institutions") ||
        inertiaPage.component.startsWith("Admin/Settings") ||
        inertiaPage.component.startsWith("Admin/Mails") ||
        isClosingsPage
    );
});

const isPageResourceGroups = computed(() => {
    const isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "resource";
    return (
        inertiaPage.component.startsWith("Admin/ResourceGroups") ||
        inertiaPage.component.startsWith("Admin/Resources") ||
        isClosingsPage
    );
});

const isPageUsers = computed(() => {
    return inertiaPage.component.startsWith("Admin/Users");
});

const isPageRoles = computed(() => {
    return inertiaPage.component.startsWith("Admin/Roles");
});

const isPagePermissions = computed(() => {
    return inertiaPage.component.startsWith("Admin/Permissions");
});

const isPagePermissionGroups = computed(() => {
    return inertiaPage.component.startsWith("Admin/PermissionGroups");
});

const isPageStats = computed(() => {
    return inertiaPage.component.startsWith("Admin/Stats");
});

const getExitUri = computed(() => {
    if (institution?.slug && resourceGroup?.slug) {
        return "/" + institution?.slug + "/" + resourceGroup?.slug + "/home";
    }

    return "/";
});
</script>
